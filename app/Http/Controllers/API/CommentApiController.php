<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommentApiController extends Controller
{
    use ResponseTrait;
    public function getAll(Request $request)
    {
        // Validate community_post_id from query parameters or body
        $validator = Validator::make($request->all(), [
            'community_post_id' => 'required|exists:community_posts,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            // Fetch comments with related user, ordered by latest
            $comments = Comment::with('user:id,name')
                ->where('community_post_id', $request->community_post_id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Format each comment
            $formattedComments = $comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'community_post_id' => $comment->community_post_id,
                    'comment' => $comment->comment,
                    'created_at' => Carbon::parse($comment->created_at)->diffForHumans(),
                    'updated_at' => Carbon::parse($comment->updated_at)->diffForHumans(),
                    'user' => $comment->user ? [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ] : null,
                ];
            });

            return $this->sendResponse($formattedComments, 'Comments fetched successfully.');
        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }



    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'community_post_id' => 'required|exists:community_posts,id',
            'comment' => 'required|string|max:2000',
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

            $comment = new Comment();
            $comment->user_id = $user->id;
            $comment->community_post_id = $validatedData['community_post_id'];
            $comment->comment = $validatedData['comment'];
            $comment->save();

            DB::commit();

            return $this->sendResponse($comment, 'Comment added successfully.', '', 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }


    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|integer|exists:comments,id',
            'comment' => 'required|string',
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

            // Ensure the comment belongs to the authenticated user
            $comment = Comment::where('id', $validatedData['comment_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$comment) {
                return $this->sendError('Comment not found or unauthorized access.', [], 404);
            }

            // Update the comment content
            $comment->comment = $validatedData['comment'];
            $comment->save();

            DB::commit();

            $comment->created_at_human = $comment->created_at->diffForHumans();
            $comment->updated_at_human = $comment->updated_at->diffForHumans();

            return $this->sendResponse($comment, 'Comment updated successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();

            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }



    public function destroy(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|integer|exists:comments,id',
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

            $comment = Comment::where('id', $request->comment_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$comment) {
                return $this->sendError('Comment not found or unauthorized access.', [], 404);
            }

            $comment->delete();

            DB::commit();

            return $this->sendResponse([], 'Comment deleted successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }


}
