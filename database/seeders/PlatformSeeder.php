<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('platforms')->insert([
            [
                'name' => 'YouTube',
                'slug' => 'youtube',
                'timezone' => 'UTC',
                'recommended_uploads_per_week' => 4,
                'best_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday']),
                'best_times' => json_encode(['12:00-15:00', '19:00-22:00']),
                'allow_same_day_uploads' => false,
                'min_hours_between_uploads' => null,
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'TikTok',
                'slug' => 'tiktok',
                'timezone' => 'UTC',
                'recommended_uploads_per_week' => 4,
                'best_days' => json_encode(['tuesday', 'thursday', 'saturday']),
                'best_times' => json_encode(['10:00-13:00', '19:00-21:00']),
                'allow_same_day_uploads' => false,
                'min_hours_between_uploads' => null,
                'is_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Instagram Reels',
                'slug' => 'instagram_reels',
                'timezone' => 'UTC',
                'recommended_uploads_per_week' => 4,
                'best_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'best_times' => json_encode(['11:00-13:00', '18:00-20:00']),
                'allow_same_day_uploads' => false,
                'min_hours_between_uploads' => null,
                'is_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Facebook Reels',
                'slug' => 'facebook_reels',
                'timezone' => 'UTC',
                'recommended_uploads_per_week' => 4,
                'best_days' => json_encode(['tuesday', 'thursday']),
                'best_times' => json_encode(['12:00-15:00']),
                'allow_same_day_uploads' => false,
                'min_hours_between_uploads' => null,
                'is_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'X (Twitter Video)',
                'slug' => 'twitter_video',
                'timezone' => 'UTC',
                'recommended_uploads_per_week' => 4,
                'best_days' => json_encode(['wednesday', 'friday']),
                'best_times' => json_encode(['09:00-11:00']),
                'allow_same_day_uploads' => false,
                'min_hours_between_uploads' => null,
                'is_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'LinkedIn Video',
                'slug' => 'linkedin_video',
                'timezone' => 'UTC',
                'recommended_uploads_per_week' => 4,
                'best_days' => json_encode(['tuesday', 'wednesday', 'thursday']),
                'best_times' => json_encode(['08:00-10:00', '16:00-18:00']),
                'allow_same_day_uploads' => false,
                'min_hours_between_uploads' => null,
                'is_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
