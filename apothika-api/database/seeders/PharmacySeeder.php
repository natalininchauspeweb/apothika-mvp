<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PharmacySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pharmacies')->insert([
            'id' => 1,
            'name' => 'Farmacia Dalton',
            'slug' => 'farmacia-dalton',
            'logo_path' => null,
            'primary_color' => '#002D3A',   // Azul Carbón
            'secondary_color' => '#28A2A5', // Turquesa Dalton
            'surface_color' => '#F2F4F6',   // Gris Claro
            'text_color' => '#002D3A',      // Azul Carbón
            'font_preset' => 'dalton',      // Montserrat + Poppins
            'points_per_1000_ars' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}