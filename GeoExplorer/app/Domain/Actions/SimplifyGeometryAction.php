<?php

namespace App\Domain\Actions;

use Illuminate\Support\Facades\DB;

class SimplifyGeometryAction
{
    /**
     * Simplifica un objeto GeoJSON reduciendo su peso en vértices.
     *
     * @param  string  $geoJsonString  Geometría en string GeoJSON
     * @param  float  $tolerance  Tolerancia en grados (0.0001 aprox. 11 metros)
     */
    public function execute(string $geoJsonString, float $tolerance = 0.0001): string
    {
        try {
            $result = DB::selectOne(
                'SELECT ST_AsGeoJSON(ST_SimplifyPreserveTopology(ST_GeomFromGeoJSON(?), ?)) AS simplified',
                [$geoJsonString, $tolerance]
            );

            return $result?->simplified ?? $geoJsonString;
        } catch (\Throwable) {
            // Si el motor falla por topología no simplificable, devuelve la original
            return $geoJsonString;
        }
    }
}
