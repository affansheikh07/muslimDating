<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_image extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'image_path', 'is_profile_picture'];

    public function user()
    {
        return $this->belongsTo(User_profile::class, 'user_id');
    }
}
