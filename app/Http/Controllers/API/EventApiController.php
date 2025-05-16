<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EventApiController extends Controller
{
    use ResponseTrait;
    public function getAll(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $events = Event::where('user_id', $user->id)
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();

            // Add full image URL to each event
            $events->transform(function ($event) {
                $event->image_url = $event->image ? asset($event->image) : null;
                return $event;
            });

            return $this->sendResponse($events, 'Events retrieved successfully.', '', 200);
        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }


    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
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

            $event = new Event();
            $event->user_id = $user->id;
            $event->title = $validatedData['title'];
            $event->description = $validatedData['description'] ?? null;
            $event->date = $validatedData['date'];
            $event->start_time = $validatedData['start_time'];
            $event->end_time = $validatedData['end_time'];

            // 📤 Handle image upload if present
            if ($request->hasFile('image')) {
                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('image'), 'event', $randomString);
                $event->image = $filePath;
            }

            $event->save();

            DB::commit();

            $event->image_url = $event->image ? asset($event->image) : null;

            return $this->sendResponse($event, 'Event created successfully.', '', 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }


    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|integer|exists:events,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
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

            $event = Event::where('id', $validatedData['event_id'])->where('user_id', $user->id)->first();
            if (!$event) {
                return $this->sendError('Event not found or unauthorized access.', [], 404);
            }

            $event->title = $validatedData['title'];
            $event->description = $validatedData['description'] ?? $event->description;
            $event->date = $validatedData['date'];
            $event->start_time = $validatedData['start_time'];
            $event->end_time = $validatedData['end_time'];

            // Handle image replacement
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($event->image && file_exists(public_path($event->image))) {
                    Helper::fileDelete($event->image);
                }

                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('image'), 'event', $randomString);
                $event->image = $filePath;
            }

            $event->save();

            DB::commit();

            $event->image_url = $event->image ? asset($event->image) : null;

            return $this->sendResponse($event, 'Event updated successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }



    public function destroy(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|integer|exists:events,id',
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

            $event = Event::where('id', $request->event_id)->where('user_id', $user->id)->first();

            if (!$event) {
                return $this->sendError('Event not found or unauthorized access.', [], 404);
            }

            // Delete image if it exists
            if ($event->image && file_exists(public_path($event->image))) {
                Helper::fileDelete($event->image);
            }

            $event->delete();

            DB::commit();

            return $this->sendResponse([], 'Event deleted successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }



}
