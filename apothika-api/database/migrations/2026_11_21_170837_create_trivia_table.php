<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo de gamificación: trivias educativas de salud y bienestar preventivo.
     */
    public function up(): void
    {
        Schema::create('trivias', function (Blueprint $table) {
            $table->id();
            $table->string('category', 60)->default('Bienestar');
            $table->string('question', 255);
            // Array JSON de opciones (ej. ["Opción A", "Opción B", "Opción C"])
            $table->json('options');
            // Índice base 0 de la opción correcta dentro del array options
            $table->unsignedTinyInteger('correct_option_index');
            $table->text('explanation');
            $table->unsignedInteger('points_reward')->default(25);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivias');
    }
};