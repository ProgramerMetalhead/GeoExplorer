<?php

namespace App\Domain\Dtos;

readonly class GeometryFeatureDTO
{
    public function __construct(
        public int $layerId,
        public string $geometryJson,
        public array $properties = [],
        public ?string $validFrom = null,
        public ?string $validTo = null,
    ) {}

    public static function fromGeoJsonArray(array $feature, int $layerId): self
    {
        $properties = $feature['properties'] ?? [];

        $validFrom = $properties['valid_from'] ?? $properties['fecha_inicio'] ?? null;
        $validTo = $properties['valid_to'] ?? $properties['fecha_fin'] ?? null;

        return new self(
            layerId: $layerId,
            geometryJson: json_encode($feature['geometry'] ?? []),
            properties: $properties,
            validFrom: $validFrom ? date('Y-m-d H:i:s', strtotime((string) $validFrom)) : null,
            validTo: $validTo ? date('Y-m-d H:i:s', strtotime((string) $validTo)) : null,
        );
    }
}
