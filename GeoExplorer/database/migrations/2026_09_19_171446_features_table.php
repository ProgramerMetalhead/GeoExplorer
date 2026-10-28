<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layer_id')->constrained()->cascadeOnDelete();

            // Columna espacial génerica (acepta Points, LineString, Poligon, etc.) con srid 4326
            $table->geometry('geometry', subtype: 'geometry', srid: 4326);

            // Atributos dinámicos del elemento (estilisticos, etiquetas, metadata propia)
            $table->json('properties')->nullable();

            // Eje temporal
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_to')->nullable();

            $table->timestamps();

            // Índice espacial obligatorio para consultas Bouding Box rápidas
            $table->spatialIndex('geometry');

            // Índice compuesto para filtros temporales y de capa
            $table->index(['layer_id', 'valid_from', 'valid_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
