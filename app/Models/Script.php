<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Script extends Model
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
            'payload' => 'json',
            'metadata' => 'json',
        ];
    }


    public function scenes()
    {
        return $this->hasMany(ScriptScene::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

}
