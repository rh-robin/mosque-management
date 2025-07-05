<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Volunteering;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VolunteeringApiController extends Controller
{
    use ResponseTrait;

    public function getAll()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $volunteerings = Volunteering::where('user_id', $user->id)
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
                ->map(function ($item) {
                    $item->file_url = $item->file ? asset($item->file) : null;
                    return $item;
                });

            return $this->sendResponse($volunteerings, 'Volunteering records fetched successfully.', '', 200);
        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }


    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i:s|before:end_time',
            'end_time'    => 'required|date_format:H:i:s|after:start_time',
            'location'    => 'required|string|max:255',
            'description' => 'nullable|string',
            'file'        => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'status'      => 'nullable|in:active,inactive',
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

            $volunteering = new Volunteering();
            $volunteering->user_id     = $user->id;
            $volunteering->title       = $validatedData['title'];
            $volunteering->date        = $validatedData['date'];
            $volunteering->start_time  = $validatedData['start_time'];
            $volunteering->end_time    = $validatedData['end_time'];
            $volunteering->location    = $validatedData['location'];
            $volunteering->description = $validatedData['description'] ?? null;
            $volunteering->status      = $validatedData['status'] ?? 'active';

            // 📤 Handle file upload if present
            if ($request->hasFile('file')) {
                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('file'), 'volunteering', $randomString);
                $volunteering->file = $filePath;
            }

            $volunteering->save();

            DB::commit();

            // Add file_url for response
            $volunteering->file_url = $volunteering->file ? asset($volunteering->file) : null;

            return $this->sendResponse($volunteering, 'Volunteering announcement created successfully.', '', 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }


    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'volunteering_id' => 'required|integer|exists:volunteerings,id',
            'title'           => 'required|string|max:255',
            'date'            => 'required|date',
            'start_time'      => 'required|date_format:H:i:s|before:end_time',
            'end_time'        => 'required|date_format:H:i:s|after:start_time',
            'location'        => 'required|string|max:255',
            'description'     => 'nullable|string',
            'file'            => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'status'          => 'nullable|in:active,inactive',
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

            // Retrieve the volunteering record
            $volunteering = Volunteering::where('id', $validatedData['volunteering_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$volunteering) {
                return $this->sendError('Volunteering record not found or unauthorized access.', [], 404);
            }

            // Update values
            $volunteering->title       = $validatedData['title'];
            $volunteering->date        = $validatedData['date'];
            $volunteering->start_time  = $validatedData['start_time'];
            $volunteering->end_time    = $validatedData['end_time'];
            $volunteering->location    = $validatedData['location'];
            $volunteering->description = $validatedData['description'] ?? $volunteering->description;
            $volunteering->status      = $validatedData['status'] ?? $volunteering->status;

            // Handle file replacement
            if ($request->hasFile('file')) {
                // Delete old file if exists
                if ($volunteering->file && File::exists(public_path($volunteering->file))) {
                    Helper::fileDelete($volunteering->file);
                }

                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('file'), 'volunteering', $randomString);
                $volunteering->file = $filePath;
            }

            $volunteering->save();

            DB::commit();

            // Add file_url for response
            $volunteering->file_url = $volunteering->file ? asset($volunteering->file) : null;

            return $this->sendResponse($volunteering, 'Volunteering record updated successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }


    public function destroy(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'volunteering_id' => 'required|integer|exists:volunteerings,id',
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

            $volunteering = Volunteering::where('id', $request->volunteering_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$volunteering) {
                return $this->sendError('Volunteering record not found or unauthorized access.', [], 404);
            }

            // Delete attached file if it exists
            if ($volunteering->file && file_exists(public_path($volunteering->file))) {
                Helper::fileDelete($volunteering->file);
            }

            $volunteering->delete();

            DB::commit();

            return $this->sendResponse([], 'Volunteering record deleted successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }




}
