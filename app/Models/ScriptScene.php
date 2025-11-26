<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScriptScene extends Model
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
            'voiceover' => 'json',
        ];
    }

    public function script()
    {
        return $this->belongsTo(Script::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
