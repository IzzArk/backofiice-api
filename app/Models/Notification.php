<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'no_surat',
        'type',
        'data',
        'is_read'
    ];

    protected $casts = [
        'data' => 'array'
    ];
}
