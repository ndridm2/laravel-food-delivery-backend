<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // user_register
    public function userRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            'phone' => 'required|string',
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($data['password']);
        $data['roles'] = 'user';

        $user = User::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    // login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'status' => 'success',
            'message' => 'Login success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    // logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Logout success',
        ]);
    }

    // restaurant_register
    public function restaurantRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            'phone' => 'required|string',
            'restaurant_name' => 'required|string',
            'restaurant_address' => 'required|string',
            'photo' => 'required|image',
            'latlong' => 'required|string',
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        $data['roles'] = 'restaurant';

        $user = User::create($data);

        // check if photo is uploaded
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photo_name = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('images'), $photo_name);
            $user->photo = $photo_name;
            $user->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Restaurant register success',
            'data' => $user,
        ], 201);
    }

    // driverRegister
    public function driverRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            'phone' => 'required|string',
            'license_plate' => 'required|string',
            'photo' => 'required|image',
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        $data['roles'] = 'driver';

        $user = User::create($data);

        // check if photo is uploaded
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photo_name = time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('images'), $photo_name);
            $user->photo = $photo_name;
            $user->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Driver register success',
            'data' => $user,
        ], 201);
    }

    // update latlong user
    public function updateLatLong(Request $request)
    {
        $request->validate([
            'latlong' => 'required|string',
            'address' => 'required|string',
        ]);

        $user = $request->user();
        $user->latlong = $request->latlong;
        $user->address = $request->address;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Update latlong success',
            'data' => $user,
        ]);
    }

    // get all restaurant
    public function getRestaurant()
    {
        $restaurant = User::where('roles', 'restaurant')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get restaurant success',
            'data' => $restaurant,
        ]);
    }

    // updet fcm_id
    public function updateFcmId(Request $request)
    {
        $request->validate([
            'fcm_id' => 'required|string',
        ]);

        $user = $request->user();
        $user->fcm_id = $request->fcm_id;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Update FCM ID success',
            // 'data' => $user,
        ], 200);
    }
}
