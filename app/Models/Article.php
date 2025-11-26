<?php

namespace App\Models;

use App\Enums\NewsStage;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /**
     * Indicates if all mass assignment is enabled.
     *
     * @var bool
     */
    protected static $unguarded = true;

    public function news()
    {
        return $this->belongsTo(News::class);
    }

    public function script()
    {
        return $this->hasOne(Script::class);
    }
}
