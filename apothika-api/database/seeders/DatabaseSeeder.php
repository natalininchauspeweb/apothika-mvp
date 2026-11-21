<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PharmacySeeder::class,
            UserSeeder::class,
            RewardSeeder::class,
            TriviaSeeder::class,
        ]);
    }
} 