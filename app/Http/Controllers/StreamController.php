<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Video;
use Illuminate\Support\Str;
use Carbon\Carbon;


class StreamController extends Controller
{

    public function index() {
    $videos = Video::all();
    return view('stream', compact('videos'));
}

public function uploadVideo(Request $request) {
    $request->validate(['video' => 'required|mimes:mp4,webm,mov|max:50000']);
    $path = $request->file('video')->store('videos', 'public');
    Video::create(['title' => $request->title, 'file_path' => $path]);
    return back()->with('success', 'Video uploaded');
}

public function scheduleVideo(Request $request) {
    $video = Video::find($request->video_id);
    $video->update([
        'is_scheduled' => true,
        'scheduled_at' => $request->scheduled_at
    ]);
    return back()->with('success', 'Video scheduled');
}

public function startStream(Request $request) {
    return response()->json(['status'=>'stream started']);
}

public function switchCamera(Request $request) {
    return response()->json(['status'=>'camera switched']);
}

   }
