<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class ProfileApiController extends Controller
{
    use ResponseTrait;

    /**
     * Retrieve the profile of the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserProfile(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->sendError('User not authenticated', [], 401);
            }

            // Prepare response data
            $success = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'city' => $user->city,
                'role' => $user->role,
                'social_links' => $user->socialLinks->map(fn($link) => [
                    'title' => $link->title,
                    'url' => $link->url,
                ])->toArray(),
            ];

            return $this->sendResponse($success, 'User profile retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong while retrieving profile', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the profile of the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserProfile(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->sendError('User not authenticated', [], 401);
            }

            // Validate request data, excluding password and role
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'phone' => ['required', 'string', 'max:20'],
                'country' => ['required', 'string', 'max:100'],
                'city' => ['required', 'string', 'max:100'],
                'social_links' => ['nullable', 'array'],
                'social_links.*.title' => ['required_with:social_links', 'string', 'max:255'],
                'social_links.*.url' => ['required_with:social_links', 'url', 'max:255'],
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
            }

            // Update the user
            $user->update([
                'name' => $request->input('name', $user->name),
                'email' => $request->input('email', $user->email),
                'phone' => $request->input('phone', $user->phone),
                'country' => $request->input('country', $user->country),
                'city' => $request->input('city', $user->city),
            ]);

            // Handle social links
            if ($request->has('social_links')) {
                // Delete existing social links to replace with new ones
                $user->socialLinks()->delete();

                // Create new social links
                foreach ($request->social_links as $link) {
                    $user->socialLinks()->create([
                        'title' => $link['title'],
                        'url' => $link['url'],
                    ]);
                }
            }

            // Prepare response data
            $success = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'city' => $user->city,
                'role' => $user->role,
                'social_links' => $user->socialLinks->map(fn($link) => [
                    'title' => $link->title,
                    'url' => $link->url,
                ])->toArray(),
            ];

            return $this->sendResponse($success, 'User profile updated successfully');

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong during profile update', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Change the password for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userChangePassword(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->sendError('User not authenticated', [], 401);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'current_password' => ['required', 'string'],
                'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Current password is incorrect', [], 422);
            }

            // Update the password
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return $this->sendResponse([], 'User password changed successfully');

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong during password change', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve the profile of the authenticated admin.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdminProfile(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->sendError('User not authenticated', [], 401);
            }

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
                'imam_name' => $user->imam_name,
                'documents' => $user->documents ? array_map(fn($path) => asset($path), json_decode($user->documents, true)) : [],
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toDateTimeString() : null,
                'created_at' => $user->created_at->toDateTimeString(),
                'updated_at' => $user->updated_at->toDateTimeString(),
                'social_links' => $user->socialLinks->map(fn($link) => [
                    'title' => $link->title,
                    'url' => $link->url,
                ])->toArray(),
            ];

            return $this->sendResponse($success, 'Admin profile retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong while retrieving profile', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the profile of the authenticated admin.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminProfileUpdate(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->sendError('User not authenticated', [], 401);
            }

            // Validate request data, excluding phone, password, and documents
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'imam_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'contact_person_phone' => ['required', 'string', 'max:20'],
                'country' => ['required', 'string', 'max:100'],
                'city' => ['required', 'string', 'max:100'],
                'state' => ['required', 'string', 'max:100'],
                'address' => ['required', 'string', 'max:255'],
                'social_links' => ['nullable', 'array'],
                'social_links.*.title' => ['required_with:social_links', 'string', 'max:255'],
                'social_links.*.url' => ['required_with:social_links', 'url', 'max:255'],
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
            }

            // Update the admin user
            $user->update([
                'name' => $request->input('name', $user->name),
                'imam_name' => $request->input('imam_name', $user->imam_name),
                'email' => $request->input('email', $user->email),
                'contact_person_phone' => $request->input('contact_person_phone', $user->contact_person_phone),
                'country' => $request->input('country', $user->country),
                'city' => $request->input('city', $user->city),
                'state' => $request->input('state', $user->state),
                'address' => $request->input('address', $user->address),
            ]);

            // Handle social links
            if ($request->has('social_links')) {
                // Delete existing social links to replace with new ones
                $user->socialLinks()->delete();

                // Create new social links
                foreach ($request->social_links as $link) {
                    $user->socialLinks()->create([
                        'title' => $link['title'],
                        'url' => $link['url'],
                    ]);
                }
            }

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
                'imam_name' => $user->imam_name,
                'role' => $user->role,
                'social_links' => $user->socialLinks->map(fn($link) => [
                    'title' => $link->title,
                    'url' => $link->url,
                ])->toArray(),
            ];

            return $this->sendResponse($success, 'Admin profile updated successfully');

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong during profile update', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Change the password for the authenticated admin.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminChangePassword(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->sendError('User not authenticated', [], 401);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'current_password' => ['required', 'string'],
                'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Current password is incorrect', [], 422);
            }

            // Update the password
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return $this->sendResponse([], 'Admin password changed successfully');

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong during password change', ['error' => $e->getMessage()], 500);
        }
    }
}
