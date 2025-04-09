<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

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
        $message = 'User registered and logged in successfully';

        // Call sendResponse from BaseController and pass the token
        return $this->sendResponse($success, $message, $token);

        /* event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false)); */
    }
}
