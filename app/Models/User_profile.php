<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'age',
        'location',
        'ethnicity',
        'height',
        'bio',
        'martial_status',
        'children',
        'education',
        'profession',
        'gender',
        'religion',
        'religious_sector',
        'interests',
        'personality',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(UserImage::class, 'user_id');
    }

    public function profilePicture()
    {
        return $this->hasOne(UserImage::class, 'user_id')->where('is_profile_picture', true);
    }

}
