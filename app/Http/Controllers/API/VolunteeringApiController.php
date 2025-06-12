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
            'file'        => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
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

}
