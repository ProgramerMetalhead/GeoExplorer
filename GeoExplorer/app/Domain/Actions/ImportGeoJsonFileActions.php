<?php

namespace App\Domain\Actions;

use App\Domain\Dtos\GeometryFeatureDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportGeoJsonFileActions
{
    public function __construct(
        protected SimplifyGeometryAction $simplifyGeometryAction
    ) {}

    public function execute(
        string $relativeFilePath,
        int $layerId,
        int $batchSize = 500,
        bool $autoSimplify = false
    ): int {
        $fullPath = Storage::disk('local')->path($relativeFilePath);

        if (! file_exists($fullPath)) {
            throw new RuntimeException("Archivo no encontrado: {$fullPath}");
        }

        $content = file_get_contents($fullPath);
        $geoJson = json_decode($content, true);
        unset($content);

        if (! isset($geoJson['features']) || ! is_array($geoJson['features'])) {
            throw new RuntimeException('El archivo no contiene un FeatureCollection válido.');
        }

        $batch = [];
        $totalInserted = 0;

        foreach ($geoJson['features'] as $featureData) {
            if (empty($featureData['geometry'])) {
                continue;
            }

            $dto = GeometryFeatureDTO::fromGeoJsonArray($featureData, $layerId);
            $geometryJson = $dto->geometryJson;

            if ($autoSimplify) {
                $geometryJson = $this->simplifyGeometryAction->execute($geometryJson);
            }

            $batch[] = [
                'layer_id' => $dto->layerId,
                'geometry' => $geometryJson,
                'properties' => json_encode($dto->properties),
                'valid_from' => $dto->validFrom,
                'valid_to' => $dto->validTo,
                'now' => now()->toDateTimeString(),
            ];

            if (count($batch) >= $batchSize) {
                $this->insertBatch($batch);
                $totalInserted += count($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $this->insertBatch($batch);
            $totalInserted += count($batch);
        }

        return $totalInserted;
    }

    protected function insertBatch(array $rows): void
    {
        $valuesSql = [];
        $bindings = [];

        foreach ($rows as $row) {
            $valuesSql[] = '(?, ST_GeomFromGeoJSON(?, 1, 4326), ?, ?, ?, ?, ?)';
            $bindings[] = $row['layer_id'];
            $bindings[] = $row['geometry'];
            $bindings[] = $row['properties'];
            $bindings[] = $row['valid_from'];
            $bindings[] = $row['valid_to'];
            $bindings[] = $row['now'];
            $bindings[] = $row['now'];
        }

        $sql = 'INSERT INTO features (layer_id, geometry, properties, valid_from, valid_to, created_at, updated_at) VALUES '
             .implode(', ', $valuesSql);

        DB::transaction(fn () => DB::statement($sql, $bindings));
    }
}
