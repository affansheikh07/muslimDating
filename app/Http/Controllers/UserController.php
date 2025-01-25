<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Report;
use App\Models\Block;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use DB;

class UserController extends Controller
{
    public function register_user(Request $request){

    $validator = Validator::make($request->all(), [
        'first_name' => 'required|string|max:20', 
        'email' => 'required|string|email|unique:users,email', 
        'password' => 'required|string|confirmed', 
        'phone_no' => 'required|string|max:13', 
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    // Create a new user
    $user = User::create([
        'first_name' => $request->first_name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'phone_no' => $request->phone_no,
    ]);

    $token = JWTAuth::fromUser($user);

   return response()->json([
       'message' => 'User registered successfully.',
       'User' => $user,
       'token' => $token,
       'status' => 200,
    ], 200);

    }

    public function login_user(Request $request){
    
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string|min:7',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $user = User::where('email', $request->email)
                ->where('status', 'true')
                ->first();

    if (!$user) {
        return response()->json([
            'message' => 'Invalid credentials or account inactive.',
            'status' => 401,
        ], 401);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials.',
            'status' => 401,
        ], 401);
    }

    $token = JWTAuth::fromUser($user);

    return response()->json([
        'message' => 'Login successful.',
        'user' => $user,
        'token' => $token,
        'status' => 200,
    ], 200);

    }

    public function fetch_user_by_id(Request $request, $id){

    $viewerId = auth()->id();

    $user = User::with(['profile', 'images'])->find($id);

    if (!$user) {
        return response()->json([
            'message' => 'User not found.',
            'status' => 401,
        ], 401);
    }

    $followData = DB::table('follows')
        ->where('follower_id', $viewerId)
        ->where('followed_id', $id)
        ->first();

    $alreadyFollowed = $followData && $followData->status === 'accepted'; 
    $followRequestStatus = $followData ? $followData->status : null;

    $alreadyLiked = DB::table('likes')
        ->where('user_id', $viewerId)
        ->where('liked_user_id', $id)
        ->exists();

    $profile = $user->profile;
    if ($profile) {
        $profile->first_name = $user->first_name;
        $profile->profile_visibility = $user->profile_visibility;
        $profile->already_followed = $alreadyFollowed;
        $profile->follow_request_status = $followRequestStatus;
        $profile->already_liked = $alreadyLiked;
    }

    return response()->json([
        'user' => [
            'profile' => $profile,
            'images' => $user->images,
        ],
        'status' => 200,
    ]);
    
    }


    public function update_profile_visibility(Request $request){

    $user = auth()->user();

    $request->validate([
        'profile_visibility' => 'required|in:public,private',
    ]);

    $previousVisibility = $user->profile_visibility;
    $newVisibility = $request->profile_visibility;

    $user->profile_visibility = $newVisibility;
    $user->save();

    if ($previousVisibility === 'private' && $newVisibility === 'public') {
        DB::table('follows')
            ->where('followed_id', $user->id)
            ->where('status', 'pending')
            ->update(['status' => 'accepted']);
    }

    return response()->json([
        'message' => 'Profile visibility updated successfully.',
        'status' => 200,
        'profile_visibility' => $user->profile_visibility,
    ]);

    }


    public function report_user(Request $request){

    $reporterId = auth()->id();
    $reportedId = $request->reported_id;

    $validator = Validator::make($request->all(), [
        'reported_id' => 'required|exists:users,id',
        'reason' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    if ($reporterId == $reportedId) {
        return response()->json([
            'message' => 'You cannot report yourself.',
            'status' => 401,
        ], 401);
    }

    Report::create([
        'reporter_id' => $reporterId,
        'reported_id' => $reportedId,
        'reason' => $request->reason,
        'status' => 'pending',
    ]);

    return response()->json([
        'message' => 'User reported successfully.',
        'status' => 200,
    ], 200);

    }

    public function block_user(Request $request){

    $blockerId = auth()->id();
    $blockedId = $request->blocked_id;

    $validator = Validator::make($request->all(), [
        'blocked_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    if ($blockerId == $blockedId) {
        return response()->json([
            'message' => 'You cannot block yourself.',
            'status' => 401,
        ], 401);
    }

    Block::create([
        'blocker_id' => $blockerId,
        'blocked_id' => $blockedId,
    ]);

    return response()->json([
        'message' => 'User blocked successfully.',
        'status' => 200,
    ], 200);

    }

    public function unblock_user(Request $request){

    $blockerId = auth()->id();
    $blockedId = $request->blocked_id;

    $validator = Validator::make($request->all(), [
        'blocked_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $block = Block::where('blocker_id', $blockerId)
                  ->where('blocked_id', $blockedId)
                  ->first();

    if (!$block) {
        return response()->json([
            'message' => 'This user is not blocked.',
            'status' => 401,
        ], 401);
    }

    $block->delete();

    return response()->json([
        'message' => 'User unblocked successfully.',
        'status' => 200,
    ], 200);

    }









}
