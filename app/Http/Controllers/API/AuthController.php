<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        if (!Auth::attempt($request->only(['email', 'password']))) {
            return $this->errorResponse(
                'The provided credentials do not match our records',
                401
            );
        }

        $user = User::where('email', $request->email)->first();

        $user->tokens()->delete();

        $token = $user->createToken('authToken')->plainTextToken;

        $tokenHash = hash('sha256', $token);
        cache()->store('redis')->put("token_blacklist:{$tokenHash}", 1, 30 * 24 * 60 * 60);

        $rawAppToken = $request->header('APP_TOKEN');

        if ($rawAppToken === null || $rawAppToken === '') {
            return $this->errorResponse('Unauthorized', 401);
        }

        $claims = [
            'sub' => $user->id,
            'app' => $rawAppToken,
            'org_id' => $user->institution_id,
            'org_name' => $user->institution ? $user->institution->name : null,
            'iat' => now()->timestamp,
            'exp' => now()->addDays(30)->timestamp,
        ];

        $payload = base64_encode(json_encode($claims));
        $signature = base64_encode(
            hash_hmac('sha256', $payload, (string) config('app.key'), true)
        );

        $enhancedAppToken = $payload.'.'.$signature;

        $cookie = cookie('auth_token', $token, 60 * 24 * 7);

        $responseData = [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 30 * 24 * 60 * 60,
            'app_token' => $enhancedAppToken,
        ];

        return $this->successResponse($responseData, 'Login successful')->withCookie($cookie);
    }

    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        if (!Auth::attempt($request->only(['email', 'password']))) {
            return $this->errorResponse(
                'The provided credentials do not match our records',
                401
            );
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

        $responseData = [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 30 * 24 * 60 * 60, // 30 days in seconds
        ];

        return $this->successResponse($responseData, 'Token refreshed successfully');
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse($user, 'Profile retrieved successfully');
    }

    public function institution(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has institution-bound roles
        $institutionBoundRoles = ['school_teacher', 'school_admin', 'student'];
        $hasInstitutionBoundRole = $user->roles()->whereIn('name', $institutionBoundRoles)->exists();

        if (!$hasInstitutionBoundRole) {
            return $this->errorResponse(
                'Access denied. Only users with institution-bound roles can access institution information',
                403
            );
        }

        // Check if user has an institution
        if (!$user->institution_id) {
            return $this->errorResponse('No institution found for this user', 404);
        }

        // Load the institution with its settings
        $institution = $user->institution;

        if (!$institution) {
            return $this->errorResponse('No institution found for this user', 404);
        }

        $institutionData = [
            'id' => $institution->id,
            'name' => $institution->name,
            'slug' => $institution->slug,
            'domain' => $institution->domain,
            'settings' => $institution->settings ?? [],
            'created_at' => $institution->created_at,
            'updated_at' => $institution->updated_at,
        ];

        return $this->successResponse($institutionData, 'Institution information retrieved successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->successResponse([], 'Successfully logged out');
    }
}
