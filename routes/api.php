<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MatchingController;
use App\Http\Controllers\FollowController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register_user', [UserController::class, 'register_user']);
Route::post('/login_user', [UserController::class, 'login_user']);
Route::post('/fetch_user_by_id/{id}', [UserController::class, 'fetch_user_by_id']);

Route::post('/post_profile', [ProfileController::class, 'post_profile']);
Route::post('/post_user_preferences', [ProfileController::class, 'post_user_preferences']);
Route::post('/upload_user_images', [ProfileController::class, 'upload_user_images']);
Route::post('/search_profiles', [ProfileController::class, 'search_profiles']);
Route::post('/get_user_preferences', [ProfileController::class, 'get_user_preferences']);


Route::post('/find_matches/{userId}', [MatchingController::class, 'find_matches']);
Route::post('/like_user', [MatchingController::class, 'like_user']);
Route::post('/get_liked_profiles', [MatchingController::class, 'get_liked_profiles']);


Route::post('/send_follow_request', [FollowController::class, 'send_follow_request']);
Route::post('/respond_to_follow_request', [FollowController::class, 'respond_to_follow_request']);
Route::post('/get_followers', [FollowController::class, 'get_followers']);
Route::post('/get_following', [FollowController::class, 'get_following']);
Route::post('/get_pending_follower_requests', [FollowController::class, 'get_pending_follower_requests']);


