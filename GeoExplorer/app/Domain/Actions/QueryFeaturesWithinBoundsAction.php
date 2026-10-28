<?php

namespace App\Domain\Actions;

use App\Domain\Dtos\BoundingBoxDTO;
use App\Models\Feature;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QueryFeaturesWithinBoundsAction
{
    public function execute(
        BoundingBoxDTO $bbox,
        ?string $timestamp = null,
        array $layerIds = []
    ): Collection {
        $polygonWkt = $bbox->toWktPolygon();
        $isMySql = DB::connection()->getDriverName() === 'mysql';

        $rawGeomExpression = $isMySql
            ? "ST_GeomFromText(?, 4326, 'axis-order=long-lat')"
            : 'ST_GeomFromText(?, 4326)';

        $parsedTimestamp = $timestamp ? Carbon::parse($timestamp)->toDateTimeString() : null;

        return Feature::query()
            ->with(['layer:id,name,color,slug'])
            ->select([
                'id',
                'layer_id',
                'properties',
                'valid_from',
                'valid_to',
                DB::raw('ST_AsGeoJSON(geometry) as geojson_geometry'),
            ])
            ->whereRaw("ST_Intersects(geometry, {$rawGeomExpression})", [$polygonWkt])
            ->when($parsedTimestamp, function ($query, $time) {
                $query->where(function ($q) use ($time) {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', $time);
                })->where(function ($q) use ($time) {
                    $q->whereNull('valid_to')->orWhere('valid_to', '>=', $time);
                });
            })
            ->when(! empty($layerIds), function ($query) use ($layerIds) {
                $query->whereIn('layer_id', $layerIds);
            })
            ->get();
    }
}
