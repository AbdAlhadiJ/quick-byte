<?php

namespace Database\Seeders;

use App\Models\SoundEffect;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SoundEffectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $musicFiles = Storage::disk('local')->allFiles('sfx');

        foreach ($musicFiles as $file) {
            if (!preg_match('/\.(mp3|wav|ogg)$/i', $file)) {
                continue;
            }
            $parts = explode('/', $file);
            $title = pathinfo($parts[1], PATHINFO_FILENAME);

            SoundEffect::updateOrCreate(
                ['title' => $title],
                [
                    'file_path' => $file,
                ]
            );
        }
    }
}
