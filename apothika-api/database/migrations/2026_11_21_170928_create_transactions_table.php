<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Libro mayor inmutable de movimientos de puntos (ganados y canjeados).
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('pharmacy_id')
                ->constrained('pharmacies')
                ->cascadeOnDelete();

            $table->enum('type', ['purchase', 'trivia', 'redemption']);
            // Entero con signo: positivo en compras/trivias, negativo en canjes
            $table->integer('points');
            $table->string('description', 255);
            // Referencia opcional al ID de la trivia o del cupón asociado
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};