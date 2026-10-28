<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Layer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LayerController extends Controller
{
    /**
     * Display a listing of active layers.
     */
    public function index(): JsonResponse
    {
        $layers = Layer::query()
            ->where('is_active', true)
            ->select(['id', 'name', 'slug', 'color', 'is_active'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $layers,
        ]);
    }

    /**
     * Store a newly created layer in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'capa';
        $slug = $baseSlug;
        $counter = 1;

        while (Layer::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $layer = Layer::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'color' => $validated['color'] ?? '#3388ff',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => $layer,
        ], Response::HTTP_CREATED);
    }
}
