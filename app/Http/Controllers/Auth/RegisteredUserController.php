<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

use App\Traits\ResponseTrait;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisteredUserController extends Controller
{
    use ResponseTrait;
    /**
     * Display the registration view.
     */
    /*public function create(): View
    {
        return view('auth.register');
    }*/

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function userStore(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        try {
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'country' => $request->country,
                'city' => $request->city,
                'password' => Hash::make($request->password),
                'role' => 'user',
            ]);

            // Generate JWT token
            $token = auth('api')->login($user);

            // Prepare response data
            $success = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'city' => $user->city,
                'role' => $user->role,
            ];

            return $this->sendResponse($success, 'User registered and logged in successfully', $token);

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong during registration', ['error' => $e->getMessage()], 500);
        }
    }


    /*============== ADMIN STORE ====================*/
    public function adminStore(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'imam_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'contact_person_phone' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'documents' => ['required'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        try {
            // Handle document file upload
            $documentPaths = [];

            if ($request->hasFile('documents')) {
                $uploadedFiles = $request->file('documents');
                $files = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];

                foreach ($files as $file) {
                    $randomString = Str::random(10);
                    $path = Helper::fileUpload($file, 'mosque/documents', $randomString);
                    $documentPaths[] = $path;
                }
            }

            // Create the admin user
            $user = User::create([
                'name' => $request->name,
                'imam_name' => $request->imam_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'contact_person_phone' => $request->contact_person_phone,
                'country' => $request->country,
                'city' => $request->city,
                'state' => $request->state,
                'address' => $request->address,
                'mosque_name' => $request->mosque_name,
                'documents' => json_encode(array_map(fn($path) => asset($path), $documentPaths)),
                'password' => Hash::make($request->password),
                'role' => 'admin',
            ]);

            // Generate JWT token
            $token = auth('api')->login($user);

            // Prepare response data
            $success = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'contact_person_phone' => $user->contact_person_phone,
                'country' => $user->country,
                'city' => $user->city,
                'state' => $user->state,
                'address' => $user->address,
                'mosque_name' => $user->mosque_name,
                'documents_url' => array_map(fn($path) => asset($path), $documentPaths),
                'role' => $user->role,
            ];

            return $this->sendResponse($success, 'Admin registered and logged in successfully', $token);

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong during registration', ['error' => $e->getMessage()], 500);
        }
    }



}
