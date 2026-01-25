<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'fcm_token' => 'nullable|string' // Add validation for FCM token
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

        return response()->json([
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
            'email' => 'required|email',
            'password' => 'required',
            'fcm_token' => 'nullable|string' // Add validation for FCM token
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        // Update FCM token if provided
        if ($request->filled('fcm_token')) {
            $user->updateFcmToken($request->fcm_token);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
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
        return response()->json(['message' => 'Logout successful']);
    }
}
