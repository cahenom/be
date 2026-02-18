<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // =====================
    // REGISTER
    // =====================
    public function AuthRegister(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s.\-]+$/'],
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:255|confirmed',
            'fcm_token' => 'nullable|string'
        ], [
            'name.regex' => 'Nama hanya boleh mengandung huruf, angka, spasi, titik, dan strip.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'roles_id' => 2,
            'password' => Hash::make($request->password),
            'saldo' => 0, // 👈 saldo awal
            'fcm_token' => $request->fcm_token // Save FCM token if provided
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return new ApiResponseResource([
            'status' => 'success',
            'message' => 'Register Success',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 201);
    }

    // =====================
    // LOGIN
    // =====================
    public function AuthLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6|max:255',
            'fcm_token' => 'nullable|string'
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ], 401);
        }

        $user = Auth::user();

        // Update FCM token if provided
        if ($request->filled('fcm_token')) {
            $user->updateFcmToken($request->fcm_token);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return new ApiResponseResource([
            'status' => 'success',
            'message' => 'Login Success',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    // =====================
    // LOGOUT
    // =====================
    public function AuthLogout(Request $request)
    {
        $request->user()->tokens()->delete();

        return new ApiResponseResource([
            'status' => 'success',
            'message' => 'Logout successful',
            'data' => null
        ]);
    }
}
