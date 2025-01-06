<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    use HasFactory;

    protected $fillable = [
        'follower_id',
        'followed_id',
        'status',
    ];

    /**
     * The user who sent the follow request.
     */
    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * The user who is being followed.
     */
    public function followed()
    {
        return $this->belongsTo(User::class, 'followed_id');
    }
}
