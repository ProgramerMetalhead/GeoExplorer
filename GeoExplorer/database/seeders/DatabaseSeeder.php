<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Layer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $layer = Layer::firstOrCreate(
            ['slug' => 'zona-centro'],
            [
                'name' => 'Zona Centro y Sitios de Interés',
                'color' => '#e63946',
            ]
        );

        // 1. Insertar Punto: ST_GeomFromText(wkt, srid)
        Feature::create([
            'layer_id' => $layer->id,
            'geometry' => DB::raw("ST_GeomFromText('POINT(-99.1412 23.7369)', 4326)"),
            'properties' => ['name' => 'Plaza Hidalgo', 'category' => 'Hito'],
            'valid_from' => '1825-01-01 00:00:00',
        ]);

        // 2. Insertar Polígono: ST_GeomFromText(wkt, srid)
        $polygonWKT = 'POLYGON((-99.145 23.730, -99.135 23.730, -99.135 23.740, -99.145 23.740, -99.145 23.730))';
        Feature::create([
            'layer_id' => $layer->id,
            'geometry' => DB::raw("ST_GeomFromText('{$polygonWKT}', 4326)"),
            'properties' => ['name' => 'Perímetro Histórico', 'zone_type' => 'Preservación'],
            'valid_from' => '1910-05-15 00:00:00',
            'valid_to' => '2030-12-31 23:59:59',
        ]);
    }
}
