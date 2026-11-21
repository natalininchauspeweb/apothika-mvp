<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cupones generados por pacientes al canjear puntos y validados en caja.
     */
    public function up(): void
    {
        Schema::create('redeemed_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('pharmacy_id')
                ->constrained('pharmacies')
                ->cascadeOnDelete();
            $table->foreignId('reward_id')
                ->constrained('rewards')
                ->cascadeOnDelete();

            // Código único corto + legible para QR y tipeo de respaldo en mostrador
            $table->string('redeem_code', 32)->unique();
            $table->enum('status', ['available', 'used'])->default('available');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redeemed_coupons');
    }
};