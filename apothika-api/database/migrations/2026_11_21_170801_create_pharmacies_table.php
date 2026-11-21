<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de farmacias adheridas (soporte White-Label para la interfaz B2C).
     */
    public function up(): void
    {
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('logo_path')->nullable();

            // Tokens visuales editables desde el panel B2B (defaults basados en Farmacia Dalton)
            $table->string('primary_color', 7)->default('#002D3A');
            $table->string('secondary_color', 7)->default('#28A2A5');
            $table->string('surface_color', 7)->default('#F2F4F6');
            $table->string('text_color', 7)->default('#002D3A');

            // Preset curado de tipografías para proteger accesibilidad y legibilidad
            $table->string('font_preset', 30)->default('dalton');

            // Regla de negocio configurable: puntos otorgados cada $1.000 ARS de compra
            $table->unsignedInteger('points_per_1000_ars')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
    }
};