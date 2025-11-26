<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
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
            'raw_response' => 'json',
            'metadata' => 'json',
        ];
    }

    public function script()
    {
        return $this->belongsTo(Script::class);
    }
    public function scene()
    {
        return $this->belongsTo(ScriptScene::class, 'script_scene_id');
    }

}
