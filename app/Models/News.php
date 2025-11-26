<?php

namespace App\Models;

use App\Enums\NewsStage;
use Illuminate\Database\Eloquent\Model;

class News extends Model
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
            'analysis_result' => 'json',
            'meta' => 'json',
            'current_stage' => NewsStage::class,
        ];
    }

    public function setStage(NewsStage $stage)
    {
        $this->update(['current_stage' => $stage]);
    }

    public function article()
    {
        return $this->hasOne(Article::class);
    }

}
