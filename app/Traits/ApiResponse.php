<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @param array $pagination
     * @return JsonResponse
     */
    protected function successResponse($data = [], string $message = 'Success', int $code = 200, array $pagination = []): JsonResponse
    {
        $response = [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($pagination)) {
            $response['pagination'] = $pagination;
        } else {
            $response['pagination'] = (object)[];
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $code
     * @param mixed $data
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Error', int $code = 400, $data = []): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'pagination' => (object)[],
        ], $code);
    }

    /**
     * Return a validation error JSON response.
     *
     * @param array $errors
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed', int $code = 422): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => [
                'errors' => $errors
            ],
            'pagination' => (object)[],
        ], $code);
    }

    /**
     * Return a paginated success JSON response.
     *
     * @param mixed $data
     * @param array $pagination
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function paginatedResponse($data, array $pagination, string $message = 'Success', int $code = 200): JsonResponse
    {
        return $this->successResponse($data, $message, $code, $pagination);
    }
}