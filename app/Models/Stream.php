<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    protected $fillable = [
        'room_id','title','status','host_name','guest_list','pre_recorded_path','scheduled_at'
    ];

    protected $casts = [
        'guest_list' => 'array',
        'scheduled_at' => 'datetime',
    ];
}

