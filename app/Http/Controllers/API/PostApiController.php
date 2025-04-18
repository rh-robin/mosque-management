<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
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
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional image upload validation
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        $validatedData = $validator->validated();

        DB::beginTransaction();

        try {
            $user = Auth::user(); // ✅ Fix: Get authenticated user
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
                $filePath = Helper::fileUpload($request->file('image'), 'posts', $randomString);
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

}
