<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    //

        protected $fillable = ['title', 'file_path', 'is_scheduled', 'scheduled_at'];

}
