<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

/**
 * @group Example API
 * 
 * Example endpoints demonstrating the standardized API response format
 */
class ExampleController extends Controller
{
    use ApiResponse;

    /**
     * Get Users List
     * 
     * Retrieve a paginated list of users with standardized response format.
     * 
     * @queryParam page int Page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page (max 100). Example: 15
     * @queryParam search string Search term for filtering users. Example: john
     * 
     * @response 200 scenario="Success with data" {
     *   "code": 200,
     *   "message": "Users retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "created_at": "2024-01-01T00:00:00.000000Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Jane Smith",
     *       "email": "jane@example.com",
     *       "created_at": "2024-01-02T00:00:00.000000Z"
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 50,
     *     "last_page": 4,
     *     "from": 1,
     *     "to": 15
     *   }
     * }
     * 
     * @response 200 scenario="Empty result" {
     *   "code": 200,
     *   "message": "No users found",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data array Array of user objects
     * @responseField pagination object Pagination information
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        $search = $request->get('search');

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage);

        if ($users->isEmpty()) {
            return $this->successResponse([], 'No users found');
        }

        $pagination = [
            'current_page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
            'last_page' => $users->lastPage(),
            'from' => $users->firstItem(),
            'to' => $users->lastItem(),
        ];

        return $this->paginatedResponse(
            $users->items(),
            $pagination,
            'Users retrieved successfully'
        );
    }

    /**
     * Get Single User
     * 
     * Retrieve a single user by ID with standardized response format.
     * 
     * @urlParam id int required The user ID. Example: 1
     * 
     * @response 200 scenario="User found" {
     *   "code": 200,
     *   "message": "User retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "bio": "Software developer",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   },
     *   "pagination": {}
     * }
     * 
     * @response 404 scenario="User not found" {
     *   "code": 404,
     *   "message": "User not found",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data object User object or empty array if not found
     * @responseField pagination object Pagination information (empty for single resource)
     */
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        return $this->successResponse($user, 'User retrieved successfully');
    }

    /**
     * Create User
     * 
     * Create a new user with validation and standardized response format.
     * 
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password (min 8 characters). Example: password123
     * @bodyParam bio string optional The user's biography. Example: Software developer
     * 
     * @response 201 scenario="User created successfully" {
     *   "code": 201,
     *   "message": "User created successfully",
     *   "data": {
     *     "id": 3,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "bio": "Software developer",
     *     "created_at": "2024-01-03T00:00:00.000000Z",
     *     "updated_at": "2024-01-03T00:00:00.000000Z"
     *   },
     *   "pagination": {}
     * }
     * 
     * @response 422 scenario="Validation error" {
     *   "code": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "email": ["The email has already been taken."],
     *       "password": ["The password must be at least 8 characters."]
     *     }
     *   },
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data object Created user object or validation errors
     * @responseField pagination object Pagination information (empty for this endpoint)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'bio' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'bio' => $request->bio,
        ]);

        return $this->successResponse($user, 'User created successfully', 201);
    }

    /**
     * Server Error Example
     * 
     * Example endpoint that demonstrates server error response format.
     * 
     * @response 500 scenario="Server error" {
     *   "code": 500,
     *   "message": "Internal server error occurred",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Error message
     * @responseField data array Empty data array
     * @responseField pagination object Pagination information (empty for error responses)
     */
    public function serverError(): JsonResponse
    {
        return $this->errorResponse('Internal server error occurred', 500);
    }
}