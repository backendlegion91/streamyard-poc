<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    $now = now();
    $stream = \App\Models\ScheduledStream::where('scheduled_at', '<=', $now)
                 ->where('status', 'pending')
                 ->first();

    if ($stream) {
        broadcast(new \App\Events\ScheduledVideoLive($stream->video->path));
        $stream->update(['status' => 'live']);
    }
})->everyMinute();
