<?php

namespace App\Enums;

enum NewsStage: string
{
    case NEW = 'new';
    case NOVELTY_FILTERED = 'novelty_filtered';
    case ARTICLE_FETCHED = 'article_fetched';
    case SUMMARY_FETCHED = 'article_summary_fetched';
    case SCRIPT_GENERATED = 'script_generated';
    case VIDEO_ASSEMBLED = 'video_assembled';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case FAILED = 'failed';
    case REJECTED = 'rejected';
}

