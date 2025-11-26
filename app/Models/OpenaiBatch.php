<?php

namespace App\Models;

use App\Enums\BatchAction;
use App\Enums\NewsStage;
use Illuminate\Database\Eloquent\Model;

class OpenaiBatch extends Model
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
            'action' => BatchAction::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
