<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_preference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'age_range',
        'location',
        'ethnicity',
        'height',
        'bio',
        'martial_status',
        'children',
        'education',
        'profession',
        'religion',
        'religious_sector',
        'interests',
        'personality',
    ];
}
