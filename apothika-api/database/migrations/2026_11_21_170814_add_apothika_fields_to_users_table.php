<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extiende la tabla users de Laravel con rol, pertenencia a farmacia,
     * DNI (mercado argentino), saldo de puntos y código identificador QR.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('pharmacy_id')
                ->nullable()
                ->after('id')
                ->constrained('pharmacies')
                ->nullOnDelete();

            $table->enum('role', ['patient', 'pharmacy'])
                ->default('patient')
                ->after('password');

            $table->string('dni', 20)
                ->nullable()
                ->unique()
                ->after('role');

            $table->unsignedInteger('points_balance')
                ->default(0)
                ->after('dni');

            $table->string('qr_code', 64)
                ->nullable()
                ->unique()
                ->after('points_balance');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Primero eliminamos la restricción FK antes de borrar las columnas
            $table->dropForeign(['pharmacy_id']);
            $table->dropColumn([
                'pharmacy_id',
                'role',
                'dni',
                'points_balance',
                'qr_code',
            ]);
        });
    }
};