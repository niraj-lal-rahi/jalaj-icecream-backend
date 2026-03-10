<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Log;

trait ApiResponse
{
    /**
     * Send a successful API response
     */
    protected function success($data = null, string $message = 'Success', int $statusCode = 200, array $extra = [])
    {
        // Calculate response time
        $startTime = request()->server('REQUEST_TIME_FLOAT') ?? microtime(true);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2); // milliseconds

        // Log the API call
        Log::info('API Success: ' . request()->method() . ' ' . request()->path(), [
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'response_time_ms' => $responseTime,
            'status_code' => $statusCode,
            'user_id' => auth()->id(),
        ]);

        $response = [
            'status' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        // Merge any extra fields
        $response = array_merge($response, $extra);

        return response()->json($response, $statusCode);
    }

    /**
     * Send an error API response
     */
    protected function error(string $message = 'Error', int $statusCode = 500, $error = null, array $extra = [])
    {
        // Calculate response time
        $startTime = request()->server('REQUEST_TIME_FLOAT') ?? microtime(true);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2); // milliseconds

        // Determine log level based on status code
        $logLevel = $statusCode >= 500 ? 'error' : 'warning';

        // Log the API error
        Log::log($logLevel, 'API Error: ' . request()->method() . ' ' . request()->path(), [
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'status_code' => $statusCode,
            'message' => $message,
            'error' => $error,
            'response_time_ms' => $responseTime,
            'user_id' => auth()->id(),
        ]);

        $response = [
            'status' => false,
            'message' => $message,
        ];

        if ($error !== null) {
            $response['error'] = $error;
        }

        // Merge any extra fields
        $response = array_merge($response, $extra);

        return response()->json($response, $statusCode);
    }

    /**
     * Send a success response with pagination
     */
    protected function successPaginated($data, string $message = 'Success', $pagination = null)
    {
        $startTime = request()->server('REQUEST_TIME_FLOAT') ?? microtime(true);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        Log::info('API Success: ' . request()->method() . ' ' . request()->path(), [
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'response_time_ms' => $responseTime,
            'status_code' => 200,
            'user_id' => auth()->id(),
        ]);

        $response = [
            'status' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($pagination !== null) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, 200);
    }
}
