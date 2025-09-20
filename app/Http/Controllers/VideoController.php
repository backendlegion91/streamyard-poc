<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use App\Models\ScheduledStream;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'video' => 'required|mimes:mp4,webm|max:200000',
        ]);

        $path = $request->file('video')->store('videos', 'public');

        Video::create([
            'title' => $request->title,
            'path' => $path,
        ]);

        return back()->with('success', 'Video uploaded successfully!');
    }

    public function schedule(Request $request)
    {
        $request->validate([
            'video_id' => 'required|exists:videos,id',
            'scheduled_at' => 'required|date|after:now',
        ]);

        ScheduledStream::create([
            'video_id' => $request->video_id,
            'scheduled_at' => $request->scheduled_at,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Video scheduled successfully!');
    }

    public function getScheduledVideos()
    {
        return ScheduledStream::with('video')->where('status', 'pending')->get();
    }
}
