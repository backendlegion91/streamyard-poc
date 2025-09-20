<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function upload(Request $r)
    {
        $file = $r->file('file');
        $filename = Str::random(8).'_'.$file->getClientOriginalName();
        $path = $file->storeAs('uploads', $filename, 'public');
        return response()->json(['path'=>"/storage/$path"]);
    }
}

