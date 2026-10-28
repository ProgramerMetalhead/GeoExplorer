<?php

namespace App\Http\Controllers\Api;

use App\Domain\Actions\QueryFeaturesWithinBoundsAction;
use App\Domain\Dtos\BoundingBoxDTO;
use App\Domain\Services\GeoJsonExporterService;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetFeaturesByBoundsRequest;
use Illuminate\Http\JsonResponse;

class SpatialFeatureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        GetFeaturesByBoundsRequest $request,
        QueryFeaturesWithinBoundsAction $queryAction,
        GeoJsonExporterService $exporterService
    ): JsonResponse {
        $bbox = BoundingBoxDTO::fromRequest($request);

        $features = $queryAction->execute(
            bbox: $bbox,
            timestamp: $request->input('timestamp'),
            layerIds: (array) $request->input('layer_ids', [])
        );

        return response()->json($exporterService->toFeatureCollection($features));
    }
}
