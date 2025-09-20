<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduledVideoLive implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $videoPath;

    public function __construct($videoPath)
    {
        $this->videoPath = $videoPath;
    }

    public function broadcastOn()
    {
        return new Channel('live-stream');
    }

    public function broadcastAs()
    {
        return 'scheduled-video-live';
    }
}

