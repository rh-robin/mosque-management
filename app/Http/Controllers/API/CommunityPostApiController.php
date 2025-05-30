<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommunityPostApiController extends Controller
{
    use ResponseTrait;
    public function getAll()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $posts = CommunityPost::with([
                'user:id,name',
                'reacts'
            ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($post) use ($user) {
                    $likeCount = $post->reacts->where('type', 'like')->count();
                    $userReact = $post->reacts->where('user_id', $user->id)->first();

                    return [
                        'id' => $post->id,
                        'user_id' => $post->user_id,
                        'post' => $post->post,
                        'created_at' => Carbon::parse($post->created_at)->diffForHumans(),
                        'updated_at' => Carbon::parse($post->updated_at)->diffForHumans(),
                        'user' => $post->user,
                        'like_count' => $likeCount,
                        'this_user_react' => $userReact ? $userReact->type : null,
                    ];
                });

            return $this->sendResponse($posts, 'Community posts fetched successfully.');
        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }



    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'post' => 'required|string|max:3000',
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

            $communityPost = new CommunityPost();
            $communityPost->user_id = $user->id;
            $communityPost->post = $validatedData['post'];
            $communityPost->save();

            DB::commit();

            return $this->sendResponse($communityPost, 'Community post created successfully.', '', 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }


    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer|exists:community_posts,id',
            'post' => 'required|string',
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

            $communityPost = CommunityPost::where('id', $validatedData['post_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$communityPost) {
                return $this->sendError('Community post not found or unauthorized access.', [], 404);
            }

            $communityPost->post = $validatedData['post'];
            $communityPost->save();

            DB::commit();

            return $this->sendResponse($communityPost, 'Community post updated successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }

    public function destroy(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer|exists:community_posts,id',
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

            $communityPost = CommunityPost::where('id', $request->post_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$communityPost) {
                return $this->sendError('Community post not found or unauthorized access.', [], 404);
            }

            $communityPost->delete();

            DB::commit();

            return $this->sendResponse([], 'Community post deleted successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }



}
