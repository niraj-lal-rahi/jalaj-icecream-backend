<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

/**
 * ApiResponse Trait
 *
 * SINGLE SOURCE OF TRUTH for all API responses.
 * Provides unified response formatting and automatic response time logging.
 *
 * All API responses should use these methods:
 * - success() for successful operations
 * - error() for failed operations
 * - successPaginated() for paginated results
 *
 * Features:
 * - Automatic response time calculation (REQUEST_TIME_FLOAT middleware)
 * - Contextualized logging with endpoint, method, user_id, status_code
 * - Unified JSON response format across all endpoints
 * - Support for extra fields via $extra parameter
 */
trait ApiResponse
{
    // === Constants (HTTP Status Codes) ===
    private const HTTP_OK = 200;
    private const HTTP_CREATED = 201;
    private const HTTP_BAD_REQUEST = 400;
    private const HTTP_UNAUTHORIZED = 401;
    private const HTTP_FORBIDDEN = 403;
    private const HTTP_NOT_FOUND = 404;
    private const HTTP_UNPROCESSABLE_ENTITY = 422;
    private const HTTP_SERVER_ERROR = 500;

    /**
     * Send a successful API response
     *
     * Formats successful operation responses with status, message, data, and response time.
     * Automatically logs the API call with endpoint, method, user ID, and response time.
     *
     * Response Format:
     * {
     *   "status": true,
     *   "message": "Success",
     *   "data": { ... },
     *   "extra_field1": "...",  // If provided in $extra parameter
     *   "response_time_ms": 45.23
     * }
     *
     * @param mixed $data Response data (can be array, collection, or null)
     * @param string $message Human-readable success message (default: 'Success')
     * @param int $statusCode HTTP status code (default: 200)
     * @param array $extra Additional fields to merge into response (optional)
     * @return JsonResponse JSON response with 200-level status code
     */
    protected function success($data = null, string $message = 'Success', int $statusCode = 200, array $extra = []): JsonResponse
    {
        // Calculate response time from REQUEST_TIME_FLOAT set by TrackApiTime middleware
        $startTime = request()->server('REQUEST_TIME_FLOAT') ?? microtime(true);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        // Log successful API call with context
        Log::info('API Success: ' . request()->method() . ' ' . request()->path(), [
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'response_time_ms' => $responseTime,
            'status_code' => $statusCode,
            'user_id' => auth()->id(),
        ]);

        // Build response structure
        $response = [
            'status' => true,
            'message' => $message,
        ];

        // Include data if provided
        if ($data !== null) {
            $response['data'] = $data;
        }

        // Merge extra fields (token, user, count, pagination, etc.)
        $response = array_merge($response, $extra);

        // Add response time for performance monitoring
        $response['response_time_ms'] = $responseTime;

        return response()->json($response, $statusCode);
    }

    /**
     * Send an error API response
     *
     * Formats error operation responses with status, message, error details, and response time.
     * Automatically logs the API error with appropriate log level (error for 5xx, warning for others).
     *
     * Response Format:
     * {
     *   "status": false,
     *   "message": "Error message",
     *   "error": { ... },  // If provided
     *   "extra_field1": "...",  // If provided in $extra parameter
     *   "response_time_ms": 12.45
     * }
     *
     * @param string $message Human-readable error message (default: 'Error')
     * @param int $statusCode HTTP status code (default: 500)
     * @param mixed $error Error details object/array/string (optional)
     * @param array $extra Additional fields to merge into response (optional)
     * @return JsonResponse JSON response with error status code
     */
    protected function error(string $message = 'Error', int $statusCode = 500, mixed $error = null, array $extra = []): JsonResponse
    {
        // Calculate response time
        $startTime = request()->server('REQUEST_TIME_FLOAT') ?? microtime(true);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        // Determine log level based on status code
        // 5xx errors = critical issues, 4xx errors = client/validation issues
        $logLevel = $statusCode >= 500 ? 'error' : 'warning';

        // Log error with context
        Log::log($logLevel, 'API Error: ' . request()->method() . ' ' . request()->path(), [
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'status_code' => $statusCode,
            'message' => $message,
            'error' => $error,
            'response_time_ms' => $responseTime,
            'user_id' => auth()->id(),
        ]);

        // Build error response structure
        $response = [
            'status' => false,
            'message' => $message,
        ];

        // Include error details if provided
        if ($error !== null) {
            $response['error'] = $error;
        }

        // Merge extra fields
        $response = array_merge($response, $extra);

        // Add response time for performance monitoring
        $response['response_time_ms'] = $responseTime;

        return response()->json($response, $statusCode);
    }

    /**
     * Send a success response with pagination metadata
     *
     * Formats successful responses with paginated data and metadata.
     * Automatically includes response time for performance monitoring.
     *
     * Response Format (with pagination):
     * {
     *   "status": true,
     *   "message": "Success",
     *   "data": [ ... ],
     *   "pagination": {
     *     "total": 150,
     *     "per_page": 15,
     *     "current_page": 1,
     *     "last_page": 10,
     *     "from": 1,
     *     "to": 15
     *   },
     *   "response_time_ms": 78.50
     * }
     *
     * @param mixed $data Paginated data items (array or collection)
     * @param string $message Human-readable success message (default: 'Success')
     * @param array|null $pagination Pagination metadata (optional)
     * @return JsonResponse JSON response with data and pagination
     */
    protected function successPaginated(mixed $data, string $message = 'Success', ?array $pagination = null): JsonResponse
    {
        // Calculate response time
        $startTime = request()->server('REQUEST_TIME_FLOAT') ?? microtime(true);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        // Log successful paginated API call
        Log::info('API Success: ' . request()->method() . ' ' . request()->path(), [
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'response_time_ms' => $responseTime,
            'status_code' => 200,
            'user_id' => auth()->id(),
        ]);

        // Build paginated response
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $data,
        ];

        // Include pagination metadata if provided
        if ($pagination !== null) {
            $response['pagination'] = $pagination;
        }

        // Add response time for performance monitoring
        $response['response_time_ms'] = $responseTime;

        return response()->json($response, 200);
    }
}
