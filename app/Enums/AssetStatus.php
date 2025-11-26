<?php

namespace App\Enums;

enum AssetStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case QUEUED = 'queued';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
