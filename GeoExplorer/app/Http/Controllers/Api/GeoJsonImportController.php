<?php

namespace App\Http\Controllers\Api; // <-- Debe incluir "\Api"

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ImportGeoJsonRequest;
use App\Jobs\ProcessGeoJsonImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GeoJsonImportController extends Controller
{
    public function store(ImportGeoJsonRequest $request): JsonResponse
    {
        $layerId = (int) $request->input('layer_id');
        $file = $request->file('file');

        $importBatchId = (string) Str::uuid();

        $storedPath = $file->storeAs(
            'imports',
            "{$importBatchId}.geojson",
            'local'
        );

        ProcessGeoJsonImportJob::dispatch($storedPath, $layerId, $importBatchId);

        return response()->json([
            'message' => 'El archivo ha sido aceptado y se está procesando en segundo plano.',
            'batch_id' => $importBatchId,
            'layer_id' => $layerId,
        ], Response::HTTP_ACCEPTED);
    }
}
