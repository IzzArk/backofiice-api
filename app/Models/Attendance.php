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
        'location',
        'status',
        'checked_in_at',
        'checked_out_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
