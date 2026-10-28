<?php

namespace App\Domain\Services;

use Illuminate\Support\Collection;

class GeoJsonExporterService
{
    /**
     * Serializa una colección de Features al formato estándar GeoJSON FeatureCollection.
     */
    public function toFeatureCollection(Collection $features, array $metadata = []): array
    {
        $mappedFeatures = $features->map(function ($feature) {
            $geometry = is_string($feature->geojson_geometry)
                ? json_decode($feature->geojson_geometry, true)
                : $feature->geojson_geometry;

            return [
                'type' => 'Feature',
                'id' => $feature->id,
                'geometry' => $geometry,
                'properties' => array_merge(
                    $feature->properties ?? [],
                    [
                        'feature_id' => $feature->id,
                        'layer_id' => $feature->layer_id,
                        'layer_name' => $feature->layer?->name,
                        'color' => $feature->layer?->color,
                        'valid_from' => $feature->valid_from?->toIso8601String(),
                        'valid_to' => $feature->valid_to?->toIso8601String(),
                    ]
                ),
            ];
        })->values()->all();

        return [
            'type' => 'FeatureCollection',
            'features' => $mappedFeatures,
            'metadata' => array_merge([
                'total_count' => count($mappedFeatures),
                'generated_at' => now()->toIso8601String(),
            ], $metadata),
        ];
    }
}
