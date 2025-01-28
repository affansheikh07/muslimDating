<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\User_profile;
use App\Models\User_image;
use App\Models\User_preference;
use DB;

class ProfileController extends Controller
{
    public function post_profile(Request $request){

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'age' => 'required|integer|min:18|max:100',
            'location' => 'required|string|max:50',
            'ethnicity' => 'required|string|max:50',
            'height' => 'required|string|max:11',
            'bio' => 'required|string|max:255',
            'martial_status' => 'required|string|max:50',
            'children' => 'required|string|max:50',
            'education' => 'required|string|max:50',
            'profession' => 'required|string|max:50',
            'gender' => 'required|string|max:9',
            'religion' => 'required|string|max:50',
            'religious_sector' => 'required|string|max:50',
            'interests' => 'required|string|max:300',
            'personality' => 'required|string|max:300',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->first(),
                'status' => 401,
            ], 401);
        }

        $validated = $validator->validated();

        // Create or update the user profile
        $user_profile = User_profile::updateOrCreate(
            ['user_id' => $validated['user_id']],
            $validated
        );

        return response()->json([
            'message' => 'User profile saved successfully.',
            'data' => $user_profile,
            'status' => 200,
        ], 200);
    
    }

    public function post_user_preferences(Request $request){
        
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'age_range' => 'required|string|max:100',
        'location' => 'required|string|max:50',
        'ethnicity' => 'required|string|max:50',
        'height' => 'required|string|max:100',
        'martial_status' => 'required|string|max:50',
        'children' => 'required|string|max:50',
        'education' => 'required|string|max:50',
        'profession' => 'required|string|max:50',
        'religion' => 'required|string|max:50',
        'religious_sector' => 'required|string|max:50',
        'interests' => 'required|string|max:300',
        'personality' => 'required|string|max:300',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $validated = $validator->validated();

    $preferences = User_preference::updateOrCreate(
        ['user_id' => $validated['user_id']],
        $validated
    );

    return response()->json([
        'message' => 'Preferences saved successfully.',
        'data' => $preferences,
        'status' => 200,
    ], 200);

    }

    public function get_user_preferences(Request $request){

    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $preferences = User_preference::where('user_id', $request->user_id)->first();

    if ($preferences) {
        $preferences->age_min = (int) explode('-', $preferences->age_range)[0];
        $preferences->age_max = (int) explode('-', $preferences->age_range)[1];
        unset($preferences->age_range);

        return response()->json([
            'message' => 'User preferences retrieved successfully.',
            'data' => $preferences,
            'status' => 200,
        ], 200);
    }

    return response()->json([
        'message' => 'User preferences do not exist.',
        'status' => 401,
    ], 401);

    }

    public function get_user_profile(Request $request){
        
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    $profile = User_profile::where('user_id', $request->user_id)->first();

    if ($profile) {
        return response()->json([
            'message' => 'User profile retrieved successfully.',
            'data' => $profile,
            'status' => 200,
        ], 200);
    }

    return response()->json([
        'message' => 'User profile does not exist.',
        'status' => 401,
    ], 401);

    }


    public function upload_user_images(Request $request){

    $validator = Validator::make($request->all(), [
        'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate multiple images
        'user_id' => 'required|exists:user_profiles,id', // Validate user ID
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    if (User_image::where('user_id', $request->user_id)->count() + count($request->file('images')) > 6) {
        return response()->json(['message' => 'You can only upload up to 6 images.', 'status' => '401'], 401);
    }

    $images = $request->file('images');
    $uploadedImages = [];

    foreach ($images as $key => $image) {
        // Move image to the public/user_images directory
        $imageName = time() . '_' . $image->getClientOriginalName(); // Optionally add a timestamp to avoid name conflicts
        $path = $image->move(public_path('user_images'), $imageName);

        // Save image info in the database
        $uploadedImages[] = User_image::create([
            'user_id' => $request->user_id,
            'image_path' => 'user_images/' . $imageName, // Store relative path for public access
            'is_profile_picture' => $key === 0, // First image as profile picture
        ]);
    }

    return response()->json([
        'message' => 'Images uploaded successfully.',
        'images' => $uploadedImages,
        'status' => 200
    ],200);

    }


    public function get_user_images(Request $request){

    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:user_profiles,id', // Validate user ID
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()->first(),
            'status' => 401,
        ], 401);
    }

    // Fetch images for the user
    $images = User_image::where('user_id', $request->user_id)
        ->select(['id', 'image_path', 'is_profile_picture'])
        ->get();

    // Return response
    return response()->json([
        'message' => 'User images fetched successfully.',
        'status' => 200,
        'data' => $images,
    ], 200);

    }


    public function search_profiles(Request $request){

    $viewerId = auth()->id();

    Cache::forget('search_profiles_' . md5(json_encode($request->all())));
    $cacheKey = 'search_profiles_' . md5(json_encode($request->all()));

    $profiles = Cache::remember($cacheKey, 3600, function () use ($request) {
        $query = User_profile::query()
            ->select([
                'user_id', 'age', 'location', 'gender', 'interests', 'bio',
                'ethnicity', 'height', 'martial_status', 'children', 'education',
                'profession', 'religion', 'religious_sector', 'personality'
            ])
            ->with(['user:id,first_name,profile_visibility', 'user.images:id,user_id,image_path,is_profile_picture']);

        if ($request->has('age_min') && $request->has('age_max')) {
            $query->whereBetween('age', [$request->age_min, $request->age_max]);
        } else {
            return collect([]);
        }

        $filters = [
            'location' => fn($q, $value) => $q->where('location', 'like', '%' . $value . '%'),
            'interests' => fn($q, $value) => $q->where('interests', 'like', '%' . $value . '%'),
            'gender' => fn($q, $value) => $q->where('gender', $value),
            'martial_status' => fn($q, $value) => $q->where('martial_status', $value),
        ];

        foreach ($filters as $key => $filter) {
            if ($request->filled($key)) {
                $filter($query, $request->get($key));
            }
        }

        return $query->paginate(10);
    });

    $profiles->getCollection()->transform(function ($profile) use ($viewerId) {
        if (isset($profile->user)) {
            $followData = DB::table('follows')
                ->select('status')
                ->where('follower_id', $viewerId)
                ->where('followed_id', $profile->user_id)
                ->first();

            $alreadyFollowed = $followData && $followData->status === 'accepted';
            $followRequestStatus = $followData->status ?? null;

            $alreadyLiked = DB::table('likes')
                ->where('user_id', $viewerId)
                ->where('liked_user_id', $profile->user_id)
                ->exists();

            $profile = array_merge($profile->toArray(), $profile->user->toArray());
            $profile['already_followed'] = $alreadyFollowed;
            $profile['follow_request_status'] = $followRequestStatus;
            $profile['already_liked'] = $alreadyLiked;
            $profile['profile_visibility'] = $profile['user']['profile_visibility'] ?? null;

            unset($profile['user']);
        }
        return $profile;
    });

    return response()->json([
        'message' => 'Profiles fetched successfully.',
        'status' => 200,
        'data' => $profiles,
    ]);
    
    }




}
