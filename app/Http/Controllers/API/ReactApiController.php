<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\React;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReactApiController extends Controller
{
    use ResponseTrait;
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'community_post_id' => 'required|integer|exists:community_posts,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        $user = Auth::user();
        if (!$user) {
            return $this->sendError('Unauthorized', [], 401);
        }

        try {
            $react = React::where('user_id', $user->id)
                ->where('community_post_id', $request->community_post_id)
                ->first();

            $message = '';
            if ($react) {
                // Toggle behavior
                if ($react->type === 'like') {
                    $react->type = null;
                    $message = 'Reaction removed successfully';
                } else {
                    $react->type = 'like';
                    $message = 'Reaction like added successfully';
                }
                $react->save();
            } else {
                // Create new like
                React::create([
                    'user_id' => $user->id,
                    'community_post_id' => $request->community_post_id,
                    'type' => 'like',
                ]);
                $message = 'Reaction like added successfully';
            }

            return $this->sendResponse([], $message, '', 200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

}
