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
 * APIs for managing user authentication
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * User Login
     * 
     * Authenticate a user and return an access token. The token will be valid for 30 days.
     * A secure HTTP-only cookie will also be set for web authentication.
     * 
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: password123
     * 
     * @response 200 scenario="Successful login" {
     *   "code": 200,
     *   "message": "Login successful",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     },
     *     "token": "1|abcdef123456789...",
     *     "token_type": "Bearer",
     *     "expires_in": 2592000
     *   },
     *   "pagination": {}
     * }
     * 
     * @response 401 scenario="Invalid credentials" {
     *   "code": 401,
     *   "message": "The provided credentials do not match our records",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @response 422 scenario="Validation error" {
     *   "code": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "email": ["The email field is required."],
     *       "password": ["The password field is required."]
     *     }
     *   },
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data.user object User information
     * @responseField data.token string Bearer token for API authentication
     * @responseField data.token_type string Token type (always "Bearer")
     * @responseField data.expires_in int Token expiration time in seconds
     * @responseField pagination object Pagination information (empty for this endpoint)
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

        // Revoke any existing tokens for the user
        $user->tokens()->delete();

        // Generate a new token
        $token = $user->createToken('authToken')->plainTextToken;

        // Store the token in Redis blacklist with 30-day TTL
        // Using the token's SHA-256 hash as the key to avoid storing raw tokens
        $tokenHash = hash('sha256', $token);
        cache()->store('redis')->put("token_blacklist:{$tokenHash}", 1, 30 * 24 * 60 * 60);

        $cookie = cookie('auth_token', $token, 60 * 24 * 7); // set the cookie for 7 days

        $responseData = [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 30 * 24 * 60 * 60, // 30 days in seconds
        ];

        return $this->successResponse($responseData, 'Login successful')->withCookie($cookie);
    }

    /**
     * Refresh Token
     * 
     * Refresh the user's access token by providing valid credentials.
     * This will revoke the current token and generate a new one.
     * 
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: password123
     * 
     * @response 200 scenario="Token refreshed successfully" {
     *   "code": 200,
     *   "message": "Token refreshed successfully",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     },
     *     "token": "2|newtoken123456789...",
     *     "token_type": "Bearer",
     *     "expires_in": 2592000
     *   },
     *   "pagination": {}
     * }
     * 
     * @response 401 scenario="Invalid credentials" {
     *   "code": 401,
     *   "message": "The provided credentials do not match our records",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @response 422 scenario="Validation error" {
     *   "code": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "email": ["The email field is required."],
     *       "password": ["The password field is required."]
     *     }
     *   },
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data.user object User information
     * @responseField data.token string New bearer token for API authentication
     * @responseField data.token_type string Token type (always "Bearer")
     * @responseField data.expires_in int Token expiration time in seconds
     * @responseField pagination object Pagination information (empty for this endpoint)
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
     * Get Student Profile
     * 
     * Retrieve the authenticated student's profile information.
     * 
     * 
     * @response 200 scenario="Profile retrieved successfully" {
     *   "code": 200,
     *   "message": "Profile retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   },
     *   "pagination": {}
     * }
     * 
     * @response 401 scenario="Unauthenticated" {
     *   "code": 401,
     *   "message": "Unauthenticated",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data object User information
     * @responseField pagination object Pagination information (empty for this endpoint)
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse($user, 'Profile retrieved successfully');
    }

    /**
     * Get Student Institution
     * 
     * Retrieve the institution information for the authenticated student.
     * Only students with institution-bound roles can access this endpoint.
     * 
     * @authenticated
     * 
     * @response 200 scenario="Institution retrieved successfully" {
     *   "code": 200,
     *   "message": "Institution information retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Harvard University",
     *     "slug": "harvard-university",
     *     "domain": "harvard.edu",
     *     "settings": {
     *       "timezone": "America/New_York",
     *       "academic_year": "2024-2025",
     *       "contact_email": "admin@harvard.edu"
     *     },
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   },
     *   "pagination": {}
     * }
     * 
     * @response 404 scenario="No institution found" {
     *   "code": 404,
     *   "message": "No institution found for this user",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @response 403 scenario="Access denied" {
     *   "code": 403,
     *   "message": "Access denied. Only users with institution-bound roles can access institution information",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @response 401 scenario="Unauthenticated" {
     *   "code": 401,
     *   "message": "Unauthenticated",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data object Institution information
     * @responseField data.id int Institution ID
     * @responseField data.name string Institution name
     * @responseField data.slug string Institution slug
     * @responseField data.domain string Institution domain
     * @responseField data.settings object Institution settings and configuration
     * @responseField data.created_at string Institution creation timestamp
     * @responseField data.updated_at string Institution last update timestamp
     * @responseField pagination object Pagination information (empty for this endpoint)
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
     * User Logout
     * 
     * Logout the authenticated user by revoking all their tokens.
     * This will invalidate the current session and require re-authentication.
     * 
     * @authenticated
     * 
     * @response 200 scenario="Successful logout" {
     *   "code": 200,
     *   "message": "Successfully logged out",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @response 401 scenario="Unauthenticated" {
     *   "code": 401,
     *   "message": "Unauthenticated",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data array Empty data array
     * @responseField pagination object Pagination information (empty for this endpoint)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->successResponse([], 'Successfully logged out');
    }
}
