<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_path',
        'image_check_out',
        'location',
        'status',
        'status_check_in',
        'status_check_out',
        'checked_in_at',
        'checked_out_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
