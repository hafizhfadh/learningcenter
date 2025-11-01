<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;



class AuthController extends Controller
{
    // Login endpoint
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'email|required',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only(['email', 'password']))) {
            return response()->json(["message" => "The provided credentials do not match our records"], 401);
        }

        $user = User::where('email', $request->email)->first();

        // Revoke any existing tokens for the user
        $user->tokens()->delete();

        // Generate a new token
        $token = $user->createToken('authToken')->plainTextToken;

        // Store the token in Redis blacklist with 30-day TTL
        // Using the token's SHA-256 hash as the key to avoid storing raw tokens
        $tokenHash = hash('sha256', $token);
        cache()->store('redis')->put("token_blacklist:{$tokenHash}", 1, 30 * 24 * 60 * 60);

        $cookie = cookie('auth_token', $token, 60 * 24 * 7); // set the cookie for 7 days

        return response()->json(['user' => $user, 'token' => $token])->withCookie($cookie);
    }

    // Refresh token endpoint
    public function refresh(Request $request)
    {
        $request->validate([
            'email' => 'email|required',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only(['email', 'password']))) {
            return response()->json(["message" => "The provided credentials do not match our records"], 401);
        }

        $user = User::where('email', $request->email)->first();

        // Revoke any existing tokens for the user
        $user->tokens()->delete();

        // Generate a new token
        $token = $user->createToken('authToken')->plainTextToken;

        // Store the token in Redis blacklist with 30-day TTL
        // Using the token's SHA-256 hash as the key to avoid storing raw tokens
        $tokenHash = hash('sha256', $token);
        cache()->store('redis')->put("token_blacklist:{$tokenHash}", 1, 30 * 24 * 60 * 60);
    }

    // Logout endpoint
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(["message" => "Successfully logged out"]);
    }
}
