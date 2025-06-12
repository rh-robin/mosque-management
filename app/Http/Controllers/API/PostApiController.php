<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Post;
use App\Traits\ResponseTrait;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PostApiController extends Controller
{
    use ResponseTrait;
    public function getAll(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $posts = Post::where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'description' => $post->description,
                        'image_url' => $post->image ? asset($post->image) : null,
                        'created_at' => $post->created_at,
                        'type' => 'post',
                    ];
                });

            $events = Event::where('user_id', $user->id)
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'date' => $event->date,
                        'start_time' => $event->start_time,
                        'image_url' => $event->image ? asset($event->image) : null,
                        'created_at' => $event->created_at,
                        'type' => 'event',
                    ];
                });

            $advertisements = Advertisement::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($advertise) {
                    return [
                        'id' => $advertise->id,
                        'title' => $advertise->title,
                        'description' => $advertise->description,
                        'image_url' => $advertise->image ? asset($advertise->image) : null,
                        'created_at' => $advertise->created_at,
                        'type' => 'advertisement',
                    ];
                });

            $faqs = Faq::where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'title' => $faq->question,
                        'description' => $faq->answer,
                        'created_at' => $faq->created_at,
                        'type' => 'faq',
                    ];
                });

            // Merge all collections into one
            $allItems = $posts->merge($events)->merge($advertisements)->merge($faqs);

            // Sort by created_at descending
            $allItems = $allItems->sortByDesc('created_at')->values();

            return $this->sendResponse($allItems, 'Data retrieved successfully.', '', 200);
        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }



    /*public function getAll()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $posts = Post::where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(function ($post) {
                    $post->image_url = $post->image ? asset($post->image) : null;
                    return $post;
                });

            return $this->sendResponse($posts, 'Posts fetched successfully.', '', 200);
        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }*/


    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Optional image upload validation
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

            $post = new Post();
            $post->user_id = $user->id;
            $post->title = $validatedData['title'];
            $post->description = $validatedData['description'] ?? null;

            // 📤 Handle image upload if present
            if ($request->hasFile('image')) {
                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('image'), 'post', $randomString);
                $post->image = $filePath;
            }

            $post->save();

            DB::commit();

            $post->image_url = $post->image ? asset($post->image) : null;
            return $this->sendResponse($post, 'Post created successfully.', '', 201);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError($exception->getMessage(), [],  500);
        }
    }



    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer|exists:posts,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
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

            $post = Post::where('id', $validatedData['post_id'])->where('user_id', $user->id)->first();
            if (!$post) {
                return $this->sendError('Post not found or unauthorized access.', [], 404);
            }

            $post->title = $validatedData['title'];
            $post->description = $validatedData['description'] ?? $post->description;

            // Handle image replacement
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($post->image && File::exists(public_path($post->image))) {
                    Helper::fileDelete($post->image);
                    //File::delete(public_path($post->image));
                }

                $randomString = Str::random(10);
                $filePath = Helper::fileUpload($request->file('image'), 'post', $randomString);
                $post->image = $filePath;
            }

            $post->save();

            DB::commit();

            $post->image_url = $post->image ? asset($post->image) : null;
            return $this->sendResponse($post, 'Post updated successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }



    public function destroy(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer|exists:posts,id',
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

            $post = Post::where('id', $request->post_id)->where('user_id', $user->id)->first();

            if (!$post) {
                return $this->sendError('Post not found or unauthorized access.', [], 404);
            }

            // Delete image if it exists
            if ($post->image && file_exists(public_path($post->image))) {
                Helper::fileDelete($post->image);
            }

            $post->delete();

            DB::commit();

            return $this->sendResponse([], 'Post deleted successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }

}
