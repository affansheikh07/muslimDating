<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'email',
        'password',
        'phone_no'
    ];

    public function profile()
    {
        return $this->hasOne(User_profile::class, 'user_id');
    }

    public function images()
    {
        return $this->hasMany(User_image::class, 'user_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'user_id');
    }

    public function likedBy()
    {
        return $this->hasMany(Like::class, 'liked_user_id');
    }

    /**
     * Users that this user is following.
     */
    public function following()
    {
        return $this->hasMany(Follow::class, 'follower_id')
                    ->where('status', 'accepted');
    }

    /**
     * Users that are following this user.
     */
    public function followers()
    {
        return $this->hasMany(Follow::class, 'followed_id')
                    ->where('status', 'accepted');
    }

    /**
     * Pending follow requests this user has sent.
     */
    public function sentFollowRequests()
    {
        return $this->hasMany(Follow::class, 'follower_id')
                    ->where('status', 'pending');
    }

    /**
     * Pending follow requests this user has received.
     */
    public function receivedFollowRequests()
    {
        return $this->hasMany(Follow::class, 'followed_id')
                    ->where('status', 'pending');
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the JWT subject claim.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}

