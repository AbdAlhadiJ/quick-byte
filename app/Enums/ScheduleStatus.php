<?php

namespace App\Enums;

enum ScheduleStatus: string
{
    case PENDING = 'pending';
    case UPLOADED = 'uploaded';
    case FAILED = 'failed';
}
