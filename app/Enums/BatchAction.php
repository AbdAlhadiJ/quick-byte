<?php

namespace App\Enums;

enum BatchAction:string
{
    case SCRIPT_GENERATING = 'script_generating';
    case EMBEDDING = 'embedding';
}
