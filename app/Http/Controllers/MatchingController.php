<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\User_profile;
use App\Models\Follow;
use App\Models\User_preference;
use App\Models\Like;

class MatchingController extends Controller
{
    
    public function find_matches(Request $request){

    $viewerId = auth()->id(); 

    $user_profile = User_profile::where('user_id', $viewerId)->first();
    if (!$user_profile) {
        return response()->json([
            'message' => 'User profile not found.',
            'status' => 401,
        ], 401);
    }

    $user_preference = User_preference::where('user_id', $viewerId)->first();
    if (!$user_preference) {
        return response()->json([
            'message' => 'User preferences not set.',
            'status' => 401,
        ], 401);
    }

    $oppositeGender = $user_profile->gender === 'Male' ? 'Female' : 'Male';

    $query = User::join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
        ->leftJoin('user_images', function ($join) {
            $join->on('users.id', '=', 'user_images.user_id')
                 ->where('user_images.is_profile_picture', '=', 1);
        })
        ->where('user_profiles.gender', $oppositeGender)
        ->where('users.id', '!=', $viewerId);

    if ($user_preference) {
        if ($user_preference->religion) {
            $query->where('user_profiles.religion', $user_preference->religion);
        }

        if ($user_preference->age_range) {
            $ageRange = explode('-', $user_preference->age_range);
            $query->whereBetween('user_profiles.age', [$ageRange[0], $ageRange[1]]);
        }

        if ($user_preference->profession) {
            $query->where('user_profiles.profession', $user_preference->profession);
        }

        if ($user_preference->marital_status) {
            $query->where('user_profiles.marital_status', $user_preference->marital_status);
        }
    }

    $matches = $query->with(['user_images' => function ($query) {
        $query->select('id', 'user_id', 'image_path', 'is_profile_picture');
    }])
    ->select('users.*', 'user_profiles.*')
    ->distinct()
    ->get();

    $matches->transform(function ($match) use ($viewerId) {
        $match['already_followed'] = Follow::where('follower_id', $viewerId)
            ->where('followed_id', $match->user_id)
            ->where('status', 'accepted')
            ->exists();

        $match['already_liked'] = Like::where('user_id', $viewerId)
            ->where('liked_user_id', $match->user_id)
            ->exists();

        $match['images'] = $match->user_images;
        unset($match->user_images);
        return $match;
    });

    return response()->json([
        'message' => 'Matches found successfully.',
        'matches' => $matches,
        'status' => 200,
    ], 200);
    
    }

    



    public function like_user(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'liked_user_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $existingLike = Like::where('user_id', $request->user_id)
                        ->where('liked_user_id', $request->liked_user_id)
                        ->first();

    if ($existingLike) {
        return response()->json([
            'message' => 'You have already liked this user.',
            'status' => '401',
        ], 401);
    }

    Like::create([
        'user_id' => $request->user_id,
        'liked_user_id' => $request->liked_user_id,
    ]);

    return response()->json([
        'message' => 'User liked successfully.',
        'status' => 200,
    ], 200);

    }

    public function get_liked_profiles(Request $request){

    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id', // Validate user ID
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $likedProfiles = Like::with('likedUser.profile', 'likedUser.images')
        ->where('user_id', $request->user_id)
        ->get()
        ->map(function ($like) {
            return [
                'liked_user' => [
                    'id' => $like->liked_user_id,
                    'first_name' => $like->likedUser->first_name,
                    'profile' => $like->likedUser->profile,
                    'images' => $like->likedUser->images,
                ],
            ];
        });

    return response()->json([
        'liked_profiles' => $likedProfiles,
        'status' => 200,
    ]);

    }







}
