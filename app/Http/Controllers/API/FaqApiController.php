<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FaqApiController extends Controller
{
    use ResponseTrait;
    public function getAll()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $faqs = Faq::where('user_id', $user->id)
                ->latest()
                ->get()
                ->map(function ($faq) {
                    $faq->created_at_human = $faq->created_at->diffForHumans();
                    $faq->updated_at_human = $faq->updated_at->diffForHumans();
                    return $faq;
                });

            return $this->sendResponse($faqs, 'FAQs fetched successfully.', '', 200);
        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }


    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'answer'   => 'required|string',
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

            $faq = new Faq();
            $faq->user_id = $user->id;
            $faq->question = $validatedData['question'];
            $faq->answer = $validatedData['answer'];

            $faq->save();

            DB::commit();

            return $this->sendResponse($faq, 'FAQ created successfully.', '', 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }


    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'faq_id'   => 'required|integer|exists:faqs,id',
            'question' => 'required|string|max:255',
            'answer'   => 'required|string',
            'status'   => 'nullable|in:active,inactive',
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

            // Ensure the FAQ belongs to the authenticated user
            $faq = Faq::where('id', $validatedData['faq_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$faq) {
                return $this->sendError('FAQ not found or unauthorized access.', [], 404);
            }

            // Update the FAQ content
            $faq->question = $validatedData['question'];
            $faq->answer = $validatedData['answer'];
            $faq->status = $validatedData['status'] ?? $faq->status; // Preserve existing if not provided

            $faq->save();

            DB::commit();



            return $this->sendResponse($faq, 'FAQ updated successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }


    public function destroy(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'faq_id' => 'required|integer|exists:faqs,id',
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

            // Fetch FAQ owned by the user
            $faq = Faq::where('id', $request->faq_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$faq) {
                return $this->sendError('FAQ not found or unauthorized access.', [], 404);
            }

            $faq->delete();

            DB::commit();

            return $this->sendResponse([], 'FAQ deleted successfully.', '', 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], $exception->getCode() ?: 500);
        }
    }



}
