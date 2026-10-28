<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Feature extends Model
{
    protected $fillable = [
        'layer_id',
        'geometry',
        'properties',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'properties' => 'array',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function layer(): BelongsTo
    {
        return $this->belongsTo(Layer::class);
    }

    /**
     * Scope para filtrar entidades que intersectan al Bounding Box del viewport.
     * Compatible con MySQL 8.0+ y postgreSQL/PostGIS.
     */
    public function scopeWithinBoundingBox(
        Builder $query,
        float $minLng,
        float $minLat,
        float $maxLng,
        float $maxLat
    ): Builder {
        $polygonWKT = sprintf(
            'POLYGON((%F %F, %F %F, %F %F, %F %F, %F %F))',
            $minLng, $minLat,
            $maxLng, $minLat,
            $maxLng, $maxLat,
            $minLng, $maxLat,
            $minLng, $minLat
        );

        $driver = DB::connection()->getDriverName();

        // Solo MySQL 8 nativo acepta el 3er parámetro 'axis-order'. MariaDB usa 2 parámetros.
        $rawGeomExpression = ($driver === 'mysql')
            ? "ST_GeomFromText(?, 4326, 'axis-order=long-lat')"
            : 'ST_GeomFromText(?, 4326)';

        return $query->whereRaw("ST_Intersects(geometry, {$rawGeomExpression})", [$polygonWKT]);
    }

    /**
     * Scope para filtrar entidades activas en un punto específico en el tiempo
     */
    public function scopeAtTimestamp(Builder $query, ?string $timestamp): Builder
    {
        if (! $timestamp) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($timestamp) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', $timestamp);
        })->where(function (Builder $q) use ($timestamp) {
            $q->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $timestamp);
        });
    }

    /**
     * Scope para incluir la geometria serializada en formato GeoJson directamente desde el
     * motor de BD.
     */
    public function scopeSelectGeoJson(Builder $query): Builder
    {
        return $query->select([
            'id',
            'layer_id',
            'properties',
            'valid_from',
            'valid_to',
            DB::raw('ST_AsGeoJSON(geometry) as geojson_geometry'),
        ]);
    }
}
