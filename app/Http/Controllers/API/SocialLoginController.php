<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialLoginController extends Controller
{
    use ResponseTrait;
    public function SocialLogin(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required',
            'provider' => 'required|in:google,twitter',
        ]);

        try {
            $provider   = $request->provider;
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($request->token);

            if ($socialUser) {
                $user = User::where('email', $socialUser->email)
                    ->orWhere('provider_id', $socialUser->getId())
                    ->first();
                $isNewUser = false;

                if (! $user) {
                    $password = Str::random(16);
                    $user     = User::create([
                        'name'              => $socialUser->getName() ?? $socialUser->getNickname(),
                        'email'             => $socialUser->getEmail(),
                        'password'          => bcrypt($password),
                        'provider'          => $provider,
                        'provider_id'       => $socialUser->getId(),
                        'role'              => 'user',
                        'email_verified_at' => now(),
                    ]);
                    $isNewUser = true;
                }

                // Generate token
                $token = auth('api')->login($user);

                // Prepare success response
                $success = [
                    'id'    => $user->id,
                    'email' => $user->email,
                    'role'  => $user->role,
                    'selected_pet'  => $user->selected_pet,
                ];

                // Evaluate the message based on the $isNewUser condition
                $message = $isNewUser ? 'User registered and logged in successfully' : 'User logged in successfully';

                // Call sendResponse from BaseController and pass the token
                return $this->sendResponse($success, $message, $token);

            } else {
                $error         = 'Invalid credentials';
                $errorMessages = ['Invalid credentials'];
                $code          = 404;
                return $this->sendError($error, $errorMessages, $code);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 401);
        }
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

    public function getProfile()
    {
        $user = Auth::user();
        return $this->sendResponse($user, 'User profile', '', 200);
    }
}
