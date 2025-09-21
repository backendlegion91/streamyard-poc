<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });


use App\Http\Controllers\StreamController;

Route::get('/', [StreamController::class, 'index']);
Route::post('/upload-video', [StreamController::class, 'uploadVideo']);
Route::post('/schedule-video', [StreamController::class, 'scheduleVideo']);
Route::post('/start-stream', [StreamController::class, 'startStream']);
Route::post('/switch-camera', [StreamController::class, 'switchCamera']);


use App\Http\Controllers\VideoController;

// Route::get('/', function(){
//     $videos = \App\Models\Video::all();
//     return view('live', compact('videos'));
// });

Route::post('/upload-video', [VideoController::class,'upload']);
