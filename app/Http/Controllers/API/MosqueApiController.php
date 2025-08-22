<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MosqueApiController extends Controller
{
    use ResponseTrait;

    /**
     * Search for mosques (admin users) based on provided criteria.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchMosque(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->sendError('User not authenticated', [], 401);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:100'],
                'country' => ['nullable', 'string', 'max:100'],
                'imam_name' => ['nullable', 'string', 'max:255'],
                'contact_person_phone' => ['nullable', 'string', 'max:20'],
                'phone' => ['nullable', 'string', 'max:20'],
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
            }

            // Build query for admin users
            $query = User::where('role', 'admin')
                ->with('socialLinks'); // Eager load social links (not used in response but kept for potential future use)

            if ($request->has('name')) {
                $query->where('name', 'LIKE', '%' . $request->name . '%');
            }

            if ($request->has('address')) {
                $query->where('address', 'LIKE', '%' . $request->address . '%');
            }

            if ($request->has('city')) {
                $query->where('city', 'LIKE', '%' . $request->city . '%');
            }

            if ($request->has('country')) {
                $query->where('country', 'LIKE', '%' . $request->country . '%');
            }

            if ($request->has('imam_name')) {
                $query->where('imam_name', 'LIKE', '%' . $request->imam_name . '%');
            }

            if ($request->has('contact_person_phone')) {
                $query->where('contact_person_phone', 'LIKE', '%' . $request->contact_person_phone . '%');
            }

            if ($request->has('phone')) {
                $query->where('phone', 'LIKE', '%' . $request->phone . '%');
            }

            // Execute query and get results
            $mosques = $query->get();

            // Prepare response data
            $success = $mosques->map(function ($user) {
                // Combine address, city, state, and country into a single address field
                $addressParts = array_filter([
                    $user->address,
                    $user->city,
                    $user->state,
                    $user->country,
                ], fn($part) => !is_null($part) && $part !== ''); // Remove null or empty values

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'address' => implode(', ', $addressParts), // Join non-empty parts with commas
                ];
            })->toArray();

            return $this->sendResponse($success, 'Mosques retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Something went wrong while searching mosques', ['error' => $e->getMessage()], 500);
        }
    }
}
