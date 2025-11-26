<?php

namespace Database\Seeders;

use App\Models\SoundEffect;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            PlatformSeeder::class,
            MusicSeeder::class,
            SoundEffectSeeder::class,
        ]);
    }
}
