<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ResponseTrait;
    public function login(Request $request)
    {
        // Validate request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        // Get credentials
        $credentials = $request->only('email', 'password');

        // Attempt login
        /*if (!$token = auth('api')->attempt($credentials)) {
            return $this->sendError('Invalid credentials', [], 401);
        }*/

        // Retrieve authenticated user
        $user = auth('api')->user();

        if(!$user){
            return $this->sendError('Unauthorized', [], 401);
        }

        $token = auth('api')->login($user);

        // Prepare success response
        $success = [
            'id'            => $user->id,
            'email'         => $user->email,
            'role'          => $user->role,
        ];

        // Send response with token
        return $this->sendResponse($success, 'User logged in successfully', $token);
    }


    public function me()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $this->sendError('User not authenticated', [], 401);
        }

        $data = [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => $user->role,
            // Add other fields if needed
        ];

        return $this->sendResponse($data, 'User profile fetched successfully');
    }

    public function logout()
    {
        try {
            // Invalidate the token
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->sendResponse([], 'User logged out successfully', 200);
        } catch (\Exception $e) {
            return $this->sendError('Failed to log out, please try again.' . $e->getMessage(), [], 400);
        }
    }

}
