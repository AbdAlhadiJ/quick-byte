<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class ThrottlesElevenLabs
{
    /**
     * Wrap the job in a Redis throttle that:
     *  - allows up to 3 concurrent locks (“slots”)
     *  - resets those slots every 1 second (window length)
     *  - blocks up to 30 seconds waiting for a slot before giving up
     */
    public function handle($job, Closure $next)
    {
        Redis::throttle('elevenlabs:tts')
        ->block(30)
        ->allow(3)
        ->every(1)
        ->then(
            fn() => $next($job),
            fn() => $job->release(10)
        );
    }
}
