<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;

    // === Constants (HTTP Status Codes) ===
    private const HTTP_OK = 200;
    private const HTTP_UNPROCESSABLE_ENTITY = 422;
    private const HTTP_UNAUTHORIZED = 401;

    /**
     * Authenticate user with email and password
     *
     * Validates email and password, creates a Sanctum token for subsequent requests.
     * Response includes flattened token and user object for mobile app compatibility.
     *
     * @param Request $request HTTP request with 'email' and 'password'
     * @return \Illuminate\Http\JsonResponse Login response with token and user
     *
     * @response 200 {
     *   "status": true,
     *   "message": "Login successful",
     *   "data": {
     *     "token": "1|token_string",
     *     "user": { "id": 1, "email": "user@example.com", ... }
     *   }
     * }
     * @response 422 { "status": false, "message": "Email is required", "data": null }
     * @response 401 { "status": false, "message": "Invalid credentials", "data": null }
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), self::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Invalid credentials', self::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $token = $user->createToken('mobile-token')->plainTextToken;

        // Return flattened response for mobile app compatibility (data.token, data.user)
        return $this->success(null, 'Login successful', self::HTTP_OK, [
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Get authenticated user's profile
     *
     * Returns current user information from the authenticated request.
     * Requires valid Sanctum token in Authorization header.
     *
     * @param Request $request HTTP request with user() middleware applied
     * @return \Illuminate\Http\JsonResponse User profile object
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();

        return $this->success(['user' => $user], 'Profile retrieved successfully');
    }

    /**
     * Logout authenticated user
     *
     * Revokes the current Sanctum access token, invalidating it for future requests.
     * Client should clear local token storage after logout.
     *
     * @param Request $request HTTP request with user() middleware applied
     * @return \Illuminate\Http\JsonResponse Logout confirmation
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }
}
