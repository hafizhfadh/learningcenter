<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

/**
 * @group Examples
 *
 * Example endpoints demonstrating listing, detail, creation and error responses.
 */
class ExampleController extends Controller
{
    use ApiResponse;

    /**
     * List example users
     *
     * Return a paginated list of users with optional free-text search by name or email.
     *
     * @queryParam per_page int Number of items per page, maximum 100. Example: 15
     * @queryParam search string Free-text search over user name and email. Example: "john"
     * @response 200 scenario="Users found" {
     *   "code": 200,
     *   "message": "Users retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 1,
     *     "last_page": 1,
     *     "from": 1,
     *     "to": 1
     *   }
     * }
     * @response 200 scenario="No users found" {
     *   "code": 200,
     *   "message": "No users found",
     *   "data": [],
     *   "pagination": {}
     * }
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
     * Get example user
     *
     * Retrieve a single user by ID.
     *
     * @urlParam id int required The ID of the user. Example: 1
     * @response 200 scenario="User found" {
     *   "code": 200,
     *   "message": "User retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   },
     *   "pagination": {}
     * }
     * @response 404 scenario="User not found" {
     *   "code": 404,
     *   "message": "User not found",
     *   "data": [],
     *   "pagination": {}
     * }
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
     * Create example user
     *
     * Create a new user with basic profile information.
     *
     * @bodyParam name string required Full name of the user. Example: "John Doe"
     * @bodyParam email string required Unique email address of the user. Example: "john@example.com"
     * @bodyParam password string required Password with minimum length of 8 characters. Example: "password123"
     * @bodyParam bio string nullable Short biography or profile text. Example: "I love programming."
     * @response 201 scenario="User created" {
     *   "code": 201,
     *   "message": "User created successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "bio": "I love programming."
     *   },
     *   "pagination": {}
     * }
     * @response 422 scenario="Validation failed" {
     *   "code": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "email": [
     *         "The email field is required."
     *       ]
     *     }
     *   },
     *   "pagination": {}
     * }
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
     * Example server error
     *
     * Return a fixed 500-level error response to demonstrate error handling.
     *
     * @response 500 scenario="Internal server error" {
     *   "code": 500,
     *   "message": "Internal server error occurred",
     *   "data": [],
     *   "pagination": {}
     * }
     */
    public function serverError(): JsonResponse
    {
        return $this->errorResponse('Internal server error occurred', 500);
    }
}
