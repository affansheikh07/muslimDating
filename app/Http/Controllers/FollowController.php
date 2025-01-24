<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\User_profile;
use App\Models\User_image;
use App\Models\User_preference;
use App\Models\Like;
use App\Models\Follow;

class FollowController extends Controller
{
    public function send_follow_request(Request $request){

    $validator = Validator::make($request->all(), [
        'follower_id' => 'required|exists:users,id', 
        'followed_id' => 'required|exists:users,id', 
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    if ($request->follower_id == $request->followed_id) {
        return response()->json([
            'message' => 'You cannot send a follow request to yourself.',
            'status' => 401,
        ], 401);
    }

    $followedUser = User::find($request->followed_id);
    
    if (!$followedUser) {
        return response()->json([
            'message' => 'User not found.',
            'status' => 401,
        ], 401);
    }

    if ($followedUser->profile_visibility == 'public') {
        $existingFollow = Follow::where('follower_id', $request->follower_id)
                                ->where('followed_id', $request->followed_id)
                                ->first();

        if ($existingFollow) {
            return response()->json([
                'message' => 'You are already following this user.',
                'status' => 401,
            ], 401);
        }

        Follow::create([
            'follower_id' => $request->follower_id,
            'followed_id' => $request->followed_id,
            'status' => 'accepted',
        ]);

        return response()->json([
            'message' => 'Followed successfully.',
            'status' => 200,
        ], 200);
    }

    $existingFollow = Follow::where('follower_id', $request->follower_id)
                            ->where('followed_id', $request->followed_id)
                            ->first();

    if ($existingFollow) {
        return response()->json([
            'message' => 'A follow request is already pending or accepted.',
            'status' => 401,
        ], 401);
    }

    Follow::create([
        'follower_id' => $request->follower_id,
        'followed_id' => $request->followed_id,
        'status' => 'pending',
    ]);

    return response()->json([
        'message' => 'Follow request sent successfully.',
        'status' => 200,
    ], 200);

    }


    public function respond_to_follow_request(Request $request){

    $validator = Validator::make($request->all(), [
        'follower_id' => 'required|exists:users,id',
        'followed_id' => 'required|exists:users,id',
        'status' => 'required|in:accepted,rejected',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $followRequest = Follow::where('follower_id', $request->follower_id)
                           ->where('followed_id', $request->followed_id)
                           ->where('status', 'pending')
                           ->first();

    if (!$followRequest) {
        return response()->json([
            'message' => 'No pending follow request found.',
            'status' => 401,
        ], 401);
    }

    $followRequest->update([
        'status' => $request->status,
    ]);

    return response()->json([
        'message' => "Follow request {$request->status} successfully.",
        'status' => 200,
    ], 200);

    }

    public function get_pending_follower_requests(Request $request){

    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $pendingRequests = Follow::with('follower')
        ->where('followed_id', $request->user_id)
        ->where('status', 'pending')
        ->get()
        ->map(function ($follow) {
            return [
                'id' => $follow->id,
                'follower_id' => $follow->follower_id,
                'follower_name' => $follow->follower->first_name, // Assumes 'first_name' exists in the users table
                'created_at' => $follow->created_at->toDateTimeString(),
            ];
        });

    return response()->json([
        'pending_requests' => $pendingRequests,
        'status' => '200',
    ], 200);

    }


    public function get_followers(Request $request){

    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $followers = Follow::with('follower')
        ->where('followed_id', $request->user_id)
        ->where('status', 'accepted')
        ->get()
        ->map(function ($follow) {
            return [
                'id' => $follow->follower->id,
                'first_name' => $follow->follower->first_name,
            ];
        });

    return response()->json([
        'followers' => $followers,
        'status' => 200,
    ], 200);

    }

    public function get_following(Request $request){

    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $following = Follow::with('followed')
        ->where('follower_id', $request->user_id)
        ->where('status', 'accepted')
        ->get()
        ->map(function ($follow) {
            return [
                'id' => $follow->followed->id,
                'first_name' => $follow->followed->first_name,
            ];
        });

    return response()->json([
        'following' => $following,
        'status' => 200,
    ], 200);

    }

    public function unfollow(Request $request){

    $followerId = auth()->id();
    $followedId = $request->followed_id;

    $validator = Validator::make($request->all(), [
        'followed_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $follow = Follow::where('follower_id', $followerId)
                    ->where('followed_id', $followedId)
                    ->first();

    if (!$follow) {
        return response()->json([
            'message' => 'Error, this user does not exists in your following list.',
            'status' => 401,
        ], 401);
    }

    $follow->delete(); // or $follow->update(['status' => 'unfollowed']); if you want to keep the record

    return response()->json([
        'message' => 'You have unfollowed this user.',
        'status' => 200,
    ], 200);

    }

    public function remove_follower(Request $request){

    $followedId = auth()->id();
    $followerId = $request->follower_id;

    $validator = Validator::make($request->all(), [
        'follower_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $follow = Follow::where('follower_id', $followerId)
                    ->where('followed_id', $followedId)
                    ->first();

    if (!$follow) {
        return response()->json([
            'message' => 'This user is not following you.',
            'status' => 401,
        ], 401);
    }

    $follow->delete();

    return response()->json([
        'message' => 'You have removed this follower.',
        'status' => 200,
    ], 200);

    }




}
