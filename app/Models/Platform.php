<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use Cachable;

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
            'best_days' => 'json',
            'best_times' => 'json',
            'allow_same_day_uploads' => 'boolean',
            'min_hours_between_uploads' => 'integer',
        ];
    }

    public function scopeEnabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }
}
