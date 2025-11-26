<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledUpload extends Model
{
    /**
     * Indicates if all mass assignment is enabled.
     *
     * @var bool
     */
    protected static $unguarded = true;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'tags' => 'json',
            'upload_response' => 'json',
        ];
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function getCleanedTagsAttributes(): array
    {
        $tags = $this->tags ?? [];

        return array_map(fn(string $tag) => ltrim($tag, '#'), $tags);
    }
}
