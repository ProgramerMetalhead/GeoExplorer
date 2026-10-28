<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessGeoJsonImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutos de tiempo máximo de ejecución

    public int $tries = 2;

    public function __construct(
        protected string $filePath,
        protected int $layerId,
        protected string $batchId
    ) {}

    public function handle(): void
    {
        $fullPath = Storage::disk('local')->path($this->filePath);

        if (! file_exists($fullPath)) {
            Log::error("Archivo no encontrado para el lote {$this->batchId}: {$fullPath}");

            return;
        }

        $fileContent = file_get_contents($fullPath);
        $geoJson = json_decode($fileContent, true);
        unset($fileContent); // Liberar memoria del string crudo

        if (! isset($geoJson['type']) || $geoJson['type'] !== 'FeatureCollection' || ! isset($geoJson['features'])) {
            Log::error("Estructura GeoJSON inválida en lote {$this->batchId}");
            $this->cleanup();

            return;
        }

        $features = $geoJson['features'];
        unset($geoJson); // Liberar la estructura principal

        $batchSize = 500;
        $batch = [];

        foreach ($features as $feature) {
            if (! isset($feature['geometry'])) {
                continue;
            }

            $properties = $feature['properties'] ?? [];
            $validFrom = $properties['valid_from'] ?? $properties['fecha_inicio'] ?? null;
            $validTo = $properties['valid_to'] ?? $properties['fecha_fin'] ?? null;

            $batch[] = [
                'layer_id' => $this->layerId,
                'geometry' => json_encode($feature['geometry']),
                'properties' => json_encode($properties),
                'valid_from' => $validFrom ? date('Y-m-d H:i:s', strtotime($validFrom)) : null,
                'valid_to' => $validTo ? date('Y-m-d H:i:s', strtotime($validTo)) : null,
                'now' => now()->toDateTimeString(),
            ];

            if (count($batch) >= $batchSize) {
                $this->insertBatch($batch);
                $batch = [];
            }
        }

        // Insertar registros restantes
        if (count($batch) > 0) {
            $this->insertBatch($batch);
        }

        $this->cleanup();
        Log::info("Lote GeoJSON {$this->batchId} importado con éxito.");
    }

    protected function insertBatch(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

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

        DB::transaction(function () use ($sql, $bindings) {
            DB::statement($sql, $bindings);
        });
    }

    protected function cleanup(): void
    {
        if (Storage::disk('local')->exists($this->filePath)) {
            Storage::disk('local')->delete($this->filePath);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Error procesando lote {$this->batchId}: ".$exception->getMessage());
        $this->cleanup();
    }
}
