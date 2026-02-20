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
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9\s.\-]+$/'],
            'email' => 'required|email|max:50|unique:users,email',
            'phone' => 'required|string|max:15|unique:users,phone',
            'password' => 'required|string|min:6|max:50|confirmed',
            'fcm_token' => 'nullable|string',
            'referral_code' => 'nullable|string|max:50'
        ], [
            'name.regex' => 'Nama hanya boleh mengandung huruf, angka, spasi, titik, dan strip.',
        ]);

        $referredBy = null;
        if ($request->filled('referral_code')) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
            if ($referrer) {
                $referredBy = $referrer->id;
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'roles_id' => 1,
            'password' => Hash::make($request->password),
            'saldo' => 0, // 👈 saldo awal
            'fcm_token' => $request->fcm_token, // Save FCM token if provided
            'referred_by' => $referredBy
        ]);

        // Create Access Token (short-lived)
        $accessToken = $user->createToken('access-token', ['access'], now()->addMinutes(60))->plainTextToken;
        
        // Create Refresh Token (long-lived)
        $refreshToken = $user->createToken('refresh-token', ['refresh'], now()->addDays(30))->plainTextToken;

        return new ApiResponseResource([
            'status' => 'success',
            'message' => 'Register Success',
            'data' => [
                'user' => $user,
                'token' => $accessToken,
                'refresh_token' => $refreshToken
            ]
        ], 201);
    }

    // =====================
    // LOGIN
    // =====================
    public function AuthLogin(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:50', // can be email or phone
            'password' => 'required|string|min:6|max:50',
            'fcm_token' => 'nullable|string'
        ]);

        $identifier = $request->input('identifier');
        $password = $request->input('password');

        // Check if identifier is email or phone
        $fieldType = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (!Auth::attempt([$fieldType => $identifier, 'password' => $password])) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid credentials',
                'data' => null
            ], 401);
        }

        $user = Auth::user();

        // Update FCM token if provided
        if ($request->filled('fcm_token')) {
            $user->updateFcmToken($request->fcm_token);
        }

        // Create Access Token (short-lived)
        $accessToken = $user->createToken('access-token', ['access'], now()->addMinutes(60))->plainTextToken;
        
        // Create Refresh Token (long-lived)
        $refreshToken = $user->createToken('refresh-token', ['refresh'], now()->addDays(30))->plainTextToken;

        return new ApiResponseResource([
            'status' => 'success',
            'message' => 'Login Success',
            'data' => [
                'user' => $user,
                'token' => $accessToken,
                'refresh_token' => $refreshToken
            ]
        ]);
    }

    // =====================
    // REFRESH TOKEN
    // =====================
    public function AuthRefresh(Request $request)
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        // Check if the current token is indeed a refresh token
        if (!$currentToken->can('refresh')) {
            return new ApiResponseResource([
                'status' => 'error',
                'message' => 'Invalid token type for refresh',
                'data' => null
            ], 403);
        }

        // Generate new access token
        $newAccessToken = $user->createToken('access-token', ['access'], now()->addMinutes(60))->plainTextToken;

        return new ApiResponseResource([
            'status' => 'success',
            'message' => 'Token Refreshed',
            'data' => [
                'token' => $newAccessToken
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
