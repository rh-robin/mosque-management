<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdvertisementApiController extends Controller
{
    use ResponseTrait;
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

            $advertise = new Advertisement();
            $advertise->user_id = $user->id;
            $advertise->content = $validatedData['content'] ?? null;

            // 📤 Handle image upload if present
            if ($request->hasFile('image')) {
                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('image'), 'advertisement', $randomString);
                $advertise->image = $filePath;
            }

            $advertise->save();

            DB::commit();

            $advertise->image_url = $advertise->image ? asset($advertise->image) : null;
            return $this->sendResponse($advertise, 'Advertisement created successfully.', '', 201);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError($exception->getMessage(), [],  500);
        }
    }


    /*============ UPDATE ===========*/
    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'advertisement_id' => 'required|integer|exists:advertisements,id',
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
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

            $advertise = Advertisement::where('id', $validatedData['advertisement_id'])->where('user_id', $user->id)->first();
            if (!$advertise) {
                return $this->sendError('Advertisement not found or unauthorized access.', [], 404);
            }

            $advertise->content = $validatedData['content'] ?? $advertise->content;

            // Handle image replacement
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($advertise->image && file_exists(public_path($advertise->image))) {
                    Helper::fileDelete($advertise->image);
                }

                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('image'), 'advertisement', $randomString);
                $advertise->image = $filePath;
            }

            $advertise->save();

            DB::commit();

            $advertise->image_url = $advertise->image ? asset($advertise->image) : null;
            return $this->sendResponse($advertise, 'Advertisement updated successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }


    public function destroy(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'advertisement_id' => 'required|integer|exists:advertisements,id',
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

            $advertise = Advertisement::where('id', $request->advertisement_id)->where('user_id', $user->id)->first();

            if (!$advertise) {
                return $this->sendError('Advertisement not found or unauthorized access.', [], 404);
            }

            // Delete image if it exists
            if ($advertise->image && file_exists(public_path($advertise->image))) {
                Helper::fileDelete($advertise->image);
            }

            $advertise->delete();

            DB::commit();

            return $this->sendResponse([], 'Advertisement deleted successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }
}
