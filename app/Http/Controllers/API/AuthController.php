<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * @group Authentication
 *
 * Endpoints for user login, token refresh, profile, institution info and logout.
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * User login
     *
     * Authenticate a user with email and password and issue access tokens.
     * Returns a Sanctum bearer token and an enhanced APP_TOKEN tied to the user.
     *
     * @unauthenticated
     * @headerParam APP_TOKEN string required Client application token configured in app.client_tokens.
     * @bodyParam email string required The user's email address. Example: admin@learningcenter.com
     * @bodyParam password string required The user's password. Example: password
     * @response 200 scenario="Successful login" {
     *   "code": 200,
     *   "message": "Login successful",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Super User",
     *       "email": "admin@learningcenter.com"
     *     },
     *     "token": "1|abcdef123456789",
     *     "token_type": "Bearer",
     *     "expires_in": 2592000,
     *     "app_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
     *   },
     *   "pagination": {}
     * }
     * @response 401 scenario="Invalid credentials" {
     *   "code": 401,
     *   "message": "The provided credentials do not match our records",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 401 scenario="Missing client APP_TOKEN" {
     *   "code": 401,
     *   "message": "Unauthorized",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 403 scenario="Invalid client APP_TOKEN" {
     *   "code": 403,
     *   "message": "Forbidden",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 422 scenario="Validation failed" {
     *   "code": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "email": [
     *         "The email field is required."
     *       ],
     *       "password": [
     *         "The password field is required."
     *       ]
     *     }
     *   },
     *   "pagination": {}
     * }
     */
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

    /**
     * Refresh auth token
     *
     * Revoke existing tokens for the user and issue a new Sanctum bearer token.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token for the current user. Example: "Bearer 1|abcdef123456789"
     * @bodyParam email string required The user's email address. Example: student@example.com
     * @bodyParam password string required The user's current password. Example: password123
     * @response 200 scenario="Token refreshed" {
     *   "code": 200,
     *   "message": "Token refreshed successfully",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Student User",
     *       "email": "student@example.com"
     *     },
     *     "token": "2|newtoken123456789",
     *     "token_type": "Bearer",
     *     "expires_in": 2592000
     *   },
     *   "pagination": {}
     * }
     * @response 401 scenario="Invalid credentials" {
     *   "code": 401,
     *   "message": "The provided credentials do not match our records",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 422 scenario="Validation failed" {
     *   "code": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "email": [
     *         "The email field is required."
     *       ],
     *       "password": [
     *         "The password field is required."
     *       ]
     *     }
     *   },
     *   "pagination": {}
     * }
     */
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

    /**
     * Get current user profile
     *
     * Return the authenticated user's profile information.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token returned from login.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @response 200 scenario="Profile retrieved" {
     *   "code": 200,
     *   "message": "Profile retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Student User",
     *     "email": "student@example.com"
     *   },
     *   "pagination": {}
     * }
     * @response 401 scenario="Missing or invalid tokens" {
     *   "code": 401,
     *   "message": "Unauthorized",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 403 scenario="Expired app token" {
     *   "code": 403,
     *   "message": "Forbidden",
     *   "data": [],
     *   "pagination": {}
     * }
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse($user, 'Profile retrieved successfully');
    }

    /**
     * Get institution information
     *
     * Retrieve the institution associated with the authenticated user.
     * Only users with institution-bound roles can access this endpoint.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token returned from login.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @response 200 scenario="Institution retrieved" {
     *   "code": 200,
     *   "message": "Institution information retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Learning Center University",
     *     "slug": "learning-center-university",
     *     "domain": "university.example.com",
     *     "settings": [],
     *     "created_at": "2024-01-01T12:00:00Z",
     *     "updated_at": "2024-01-02T12:00:00Z"
     *   },
     *   "pagination": {}
     * }
     * @response 403 scenario="User without institution-bound role" {
     *   "code": 403,
     *   "message": "Access denied. Only users with institution-bound roles can access institution information",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 404 scenario="No institution found for user" {
     *   "code": 404,
     *   "message": "No institution found for this user",
     *   "data": [],
     *   "pagination": {}
     * }
     */
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

    /**
     * Logout
     *
     * Revoke all active tokens for the authenticated user.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token for the current user.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @response 200 scenario="Logged out" {
     *   "code": 200,
     *   "message": "Successfully logged out",
     *   "data": [],
     *   "pagination": {}
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->successResponse([], 'Successfully logged out');
    }
}
