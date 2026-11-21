<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivot N:M que registra qué trivias completó cada paciente.
     * La clave única compuesta impide cobrar puntos dos veces por la misma trivia.
     */
    public function up(): void
    {
        Schema::create('trivia_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('trivia_id')
                ->constrained('trivias')
                ->cascadeOnDelete();

            $table->boolean('answered_correctly')->default(false);
            $table->timestamp('completed_at')->useCurrent();

            $table->unique(['user_id', 'trivia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivia_user');
    }
};