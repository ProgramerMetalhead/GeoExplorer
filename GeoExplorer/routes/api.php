<?php

use App\Http\Controllers\Api\GeoJsonImportController;
use App\Http\Controllers\Api\LayerController;
use App\Http\Controllers\Api\SpatialFeatureController;
use Illuminate\Support\Facades\Route;

Route::get('/layers', [LayerController::class, 'index'])
    ->name('api.layers.index');

Route::post('/layers', [LayerController::class, 'store'])
    ->name('api.layers.store');

Route::get('/features/bounds', [SpatialFeatureController::class, 'index'])
    ->name('api.features.bounds');

Route::post('/imports/geojson', [GeoJsonImportController::class, 'store'])
    ->name('api.imports.geojson');
