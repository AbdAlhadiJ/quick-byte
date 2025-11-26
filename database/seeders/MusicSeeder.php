<?php

namespace Database\Seeders;

use App\Models\MusicCategory;
use App\Models\MusicTrack;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class MusicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $folders = Storage::disk('local')->directories('music');

        foreach ($folders as $folderPath) {
            $categoryName = basename($folderPath);

            $category = MusicCategory::firstOrCreate(
                ['name' => $categoryName]
            );

            $files = Storage::disk('local')->files($folderPath);

            foreach ($files as $filePath) {
                // skip non‑audio if you like:
                if (!preg_match('/\.(mp3|wav|ogg)$/i', $filePath)) {
                    continue;
                }

                $fileName = basename($filePath);

                 MusicTrack::firstOrCreate(
                    ['file_path' => $filePath],
                    [
                        'title' => pathinfo($fileName, PATHINFO_FILENAME),
                        'music_category_id' => $category->id,
                    ]
                );

            }
        }
    }
}
