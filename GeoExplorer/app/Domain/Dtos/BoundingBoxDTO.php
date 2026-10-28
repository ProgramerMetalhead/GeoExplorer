<?php

namespace App\Domain\Dtos;

use Illuminate\Http\Request;

readonly class BoundingBoxDTO
{
    public function __construct(
        public float $minLng,
        public float $minLat,
        public float $maxLng,
        public float $maxLat,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            minLng: (float) $request->input('min_lng'),
            minLat: (float) $request->input('min_lat'),
            maxLng: (float) $request->input('max_lng'),
            maxLat: (float) $request->input('max_lat'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            minLng: (float) $data['min_lng'],
            minLat: (float) $data['min_lat'],
            maxLng: (float) $data['max_lng'],
            maxLat: (float) $data['max_lat'],
        );
    }

    /**
     * Construye un polígono cerrado en sentido de las agujas del reloj:
     * SW -> SE -> NE -> NW -> SW
     */
    public function toWktPolygon(): string
    {
        return sprintf(
            'POLYGON((%F %F, %F %F, %F %F, %F %F, %F %F))',
            $this->minLng, $this->minLat,
            $this->maxLng, $this->minLat,
            $this->maxLng, $this->maxLat,
            $this->minLng, $this->maxLat,
            $this->minLng, $this->minLat
        );
    }
}
