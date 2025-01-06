<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

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
       'status' => '200',
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

    public function fetch_user_by_id($id){

    $user = User::with(['profile', 'images'])->find($id);

    if (!$user) {
        return response()->json([
            'message' => 'User not found.',
            'status' => 401
        ], 401);
    }

    return response()->json([
        'user' => [
            'first_name' => $user->first_name,
            'profile' => $user->profile,
            'images' => $user->images,
        ],
        'status' => 200,
    ]);

    }


}
