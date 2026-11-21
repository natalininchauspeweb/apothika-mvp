<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'pharmacy_id' => 1,
                'name' => 'Farmacia Dalton — Mostrador',
                'email' => 'farmacia@dalton.com.ar',
                'password' => Hash::make('dalton123'),
                'role' => 'pharmacy',
                'dni' => null,
                'points_balance' => 0,
                'qr_code' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'pharmacy_id' => 1,
                'name' => 'Camila Gómez',
                'email' => 'paciente@dalton.com.ar',
                'password' => Hash::make('paciente123'),
                'role' => 'patient',
                'dni' => '38456123',
                'points_balance' => 350,
                'qr_code' => 'APO-DAL-38456123',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'pharmacy_id' => 1,
                'name' => 'Lucas Fernández',
                'email' => 'lucas@dalton.com.ar',
                'password' => Hash::make('paciente123'),
                'role' => 'patient',
                'dni' => '40123987',
                'points_balance' => 120,
                'qr_code' => 'APO-DAL-40123987',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Movimientos iniciales para que el historial de Camila coincida con sus 350 pts
        DB::table('transactions')->insert([
            [
                'patient_id' => 2,
                'pharmacy_id' => 1,
                'type' => 'purchase',
                'points' => 300,
                'description' => 'Compra en mostrador ($30.000 ARS) — Dermocosmética',
                'reference_id' => null,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'patient_id' => 2,
                'pharmacy_id' => 1,
                'type' => 'purchase',
                'points' => 50,
                'description' => 'Bono de bienvenida — Club Farmacia Dalton',
                'reference_id' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'patient_id' => 3,
                'pharmacy_id' => 1,
                'type' => 'purchase',
                'points' => 120,
                'description' => 'Compra en mostrador ($12.000 ARS)',
                'reference_id' => null,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
        ]);
    }
}