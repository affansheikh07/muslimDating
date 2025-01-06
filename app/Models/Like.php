<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'liked_user_id',
    ];

    public function likedUser()
    {
        return $this->belongsTo(User::class, 'liked_user_id');
    }

}
