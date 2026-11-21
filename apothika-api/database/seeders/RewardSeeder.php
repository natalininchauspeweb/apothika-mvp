<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('rewards')->insert([
            [
                'pharmacy_id' => 1,
                'title' => 'Control de Presión Arterial sin cargo',
                'description' => 'Medición profesional en gabinete farmacéutico con registro de evolución y asesoramiento preventivo.',
                'points_cost' => 50,
                'image_path' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'pharmacy_id' => 1,
                'title' => '15% OFF en Dermocosmética seleccionada',
                'description' => 'Válido en limpiadores, sérums y cremas hidratantes de líneas dermocosméticas participantes en mostrador.',
                'points_cost' => 200,
                'image_path' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'pharmacy_id' => 1,
                'title' => '20% OFF en Protectores Solares FPS 50+',
                'description' => 'Cuidá tu piel todo el año. Aplicable a protectores faciales y corporales dermatológicamente testeados.',
                'points_cost' => 280,
                'image_path' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'pharmacy_id' => 1,
                'title' => '25% OFF en Suplementos y Vitaminas',
                'description' => 'Descuento directo en Vitamina C, Magnesio y complejos multivitamínicos de venta libre.',
                'points_cost' => 320,
                'image_path' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'pharmacy_id' => 1,
                'title' => 'Voucher $5.000 ARS en Perfumería y Cuidado Personal',
                'description' => 'Aplicable en compras superiores a $20.000 ARS en artículos de perfumería, higiene y cuidado capilar.',
                'points_cost' => 450,
                'image_path' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}