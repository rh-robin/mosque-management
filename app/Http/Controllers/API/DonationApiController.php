<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DonationApiController extends Controller
{
    use ResponseTrait;

    public function getAll(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $donations = Donation::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Add full image URL for each donation
            $donations->transform(function ($donation) {
                $donation->image_url = $donation->image ? asset($donation->image) : null;
                return $donation;
            });

            return $this->sendResponse($donations, 'Donations retrieved successfully.', '', 200);
        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }

    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'cause' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'has_limit' => 'required|in:yes,no',
            'amount_limit' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        $validatedData = $validator->validated();

        DB::beginTransaction();

        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $donation = new Donation();
            $donation->user_id = $user->id;
            $donation->cause = $validatedData['cause'];
            $donation->description = $validatedData['description'] ?? null;
            $donation->has_limit = $validatedData['has_limit'];
            $donation->amount_limit = $validatedData['amount_limit'] ?? null;
            $donation->raised_amount = 0;

            // 📤 Handle image upload if present
            if ($request->hasFile('image')) {
                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('image'), 'donation', $randomString);
                $donation->image = $filePath;
            }

            $donation->save();

            DB::commit();

            $donation->image_url = $donation->image ? asset($donation->image) : null;

            return $this->sendResponse($donation, 'Donation created successfully.', '', 201);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError($exception->getMessage(), [], 500);
        }
    }


    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'donation_id' => 'required|integer|exists:donations,id',
            'cause' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'has_limit' => 'required|in:yes,no',
            'amount_limit' => 'nullable|numeric',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        $validatedData = $validator->validated();

        DB::beginTransaction();

        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $donation = Donation::where('id', $validatedData['donation_id'])->where('user_id', $user->id)->first();
            if (!$donation) {
                return $this->sendError('Donation not found or unauthorized access.', [], 404);
            }

            $donation->cause = $validatedData['cause'];
            $donation->description = $validatedData['description'] ?? $donation->description;
            $donation->has_limit = $validatedData['has_limit'];
            $donation->amount_limit = $validatedData['amount_limit'] ?? $donation->amount_limit;
            $donation->status = $validatedData['status'];

            // Handle image replacement
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($donation->image && File::exists(public_path($donation->image))) {
                    Helper::fileDelete($donation->image);
                }

                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('image'), 'donations', $randomString);
                $donation->image = $filePath;
            }

            $donation->save();

            DB::commit();

            $donation->image_url = $donation->image ? asset($donation->image) : null;

            return $this->sendResponse($donation, 'Donation updated successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }


    public function destroy(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'donation_id' => 'required|integer|exists:donations,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $donation = Donation::where('id', $request->donation_id)->where('user_id', $user->id)->first();

            if (!$donation) {
                return $this->sendError('Donation not found or unauthorized access.', [], 404);
            }

            // Delete image if it exists
            if ($donation->image && file_exists(public_path($donation->image))) {
                Helper::fileDelete($donation->image);
            }

            $donation->delete();

            DB::commit();

            return $this->sendResponse([], 'Donation deleted successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }

}
