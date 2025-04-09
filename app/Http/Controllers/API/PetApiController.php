<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\Weight;
use App\Traits\ResponseTrait;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PetApiController extends Controller
{
    use ResponseTrait;

    public function store(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|in:dog,cat',
            'breed_id' => 'required|exists:breeds,id',
            'd_o_b' => 'required|date_format:d/m/Y',
            'gender' => 'required|in:male,female',
            'weight' => 'nullable|numeric|min:0',
            'weight_goal' => 'nullable',
            'height' => 'nullable|numeric|min:0',
            'additional_note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            //return response()->json(['errors' => $validator->errors()], 422);
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        $dob = Carbon::createFromFormat('d/m/Y', $request->d_o_b)->format('Y-m-d');

        DB::beginTransaction();
        try {
            // Handle image upload
            $file = 'image';
            $imagePath = null;
            if ($request->hasFile($file)) {
                // Upload the new file
                $randomString = Str::random(10);
                $imagePath  = Helper::fileUpload($request->file($file), 'pet', $randomString);
            }

            // Create pet record
            $pet = Pet::create([
                'user_id' => Auth::user()->id,
                'name' => $request->name,
                'category' => $request->category,
                'breed_id' => $request->breed_id,
                'd_o_b' => $dob,
                'gender' => $request->gender,
                //'age' => $request->age,
                'weight' => $request->weight,
                //'weight_goal' => $request->weight_goal,
                'height' => $request->height,
                'additional_note' => $request->additional_note,
                'image' => $imagePath,
            ]);

            $weight = new Weight();
            $weight->pet_id = $pet->id;
            $weight->current_weight = $request->current_weight;
            $weight->save();

            DB::commit();

            $success = '';
            $message = 'Pet added successfully!';
            return $this->sendResponse($success, $message, '', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), [], 500);
        }
    }


    public function update(Request $request, $id)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|in:dog,cat',
            'd_o_b' => 'required|date_format:d/m/Y',
            'gender' => 'required|in:male,female',
            'weight' => 'nullable|numeric|min:0',
            'weight_goal' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'additional_note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        $dob = Carbon::createFromFormat('d/m/Y', $request->d_o_b)->format('Y-m-d');

        DB::beginTransaction();
        try {
            // Find the pet by ID
            $pet = Pet::findOrFail($id);

            // Handle image upload if provided
            $file = 'image';
            $imagePath = $pet->image; // Keep the old image path by default
            if ($request->hasFile($file)) {
                if ($pet->image) {
                    Helper::fileDelete($pet->image);
                }
                // Upload the new file
                $randomString = Str::random(10);
                $imagePath  = Helper::fileUpload($request->file($file), 'pet', $randomString);
            }

            // Update pet record
            $pet->update([
                'name' => $request->name,
                'category' => $request->category,
                'breed_id' => $request->breed_id,
                'd_o_b' => $dob,
                'gender' => $request->gender,
                'weight' => $request->weight,
                'weight_goal' => $request->weight_goal,
                'height' => $request->height,
                'additional_note' => $request->additional_note,
                'image' => $imagePath,
            ]);

            DB::commit();

            $success = '';
            $message = 'Pet updated successfully!';
            return $this->sendResponse($success, $message, '', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), [], 500);
        }
    }


    public function myPet()
    {
        $user = Auth::user();

        $pets = Pet::where('user_id', $user->id)
            ->with('breed:id,title') // Load breed with only id and title
            ->get()
            ->map(function ($pet) {
                return [
                    'id' => $pet->id,
                    'user_id' => $pet->user_id,
                    'breed_id' => $pet->breed_id,
                    'breed_title' => $pet->breed ? $pet->breed->title : null,
                    'name' => $pet->name,
                    'category' => $pet->category,
                    'd_o_b' => \Carbon\Carbon::parse($pet->d_o_b)->format('d/m/Y'), // Convert d_o_b format
                    'gender' => $pet->gender,
                    'weight' => $pet->weight,
                    'weight_goal' => $pet->weight_goal,
                    'height' => $pet->height,
                    'additional_note' => $pet->additional_note,
                    'image' => $pet->image,
                    'created_at' => $pet->created_at,
                    'updated_at' => $pet->updated_at,
                ];
            });

        return $this->sendResponse($pets, 'Pet list', '', 200);
    }


    public function selectPet(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'selected_pet' => 'required|string|in:cat,dog',
        ]);

        if ($validator->fails()) {
            //return response()->json(['errors' => $validator->errors()], 422);
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        try {
            if(Auth::check()) {

                $user = Auth::user();
                $user->selected_pet = $request->selected_pet;
                $user->save();

                $selected_pet = $request->selected_pet;
                $selected_pet_profile = Pet::where('user_id', Auth::user()->id)->where('category', strtolower($selected_pet))->first();
                $response = [
                    'selected_pet' => $user->selected_pet,
                    'is_profile_created' => !is_null($selected_pet_profile),
                ];

                return $this->sendResponse($response, 'Select pet saved successfully.');
            }else{
                return $this->sendError('User is not authenticated. Please log in to continue.', [], 401);
            }
        }catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }



    public function selectedPet()
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->selected_pet) {
                $response = [
                    'selected_pet' => $user->selected_pet,
                ];

                return $this->sendResponse($response, 'Selected pet retrieved successfully.');
            } else {
                return $this->sendError('No pet has been selected by the user.', [], 404);
            }
        } else {
            return $this->sendError('User is not authenticated. Please log in to continue.', [], 401);
        }
    }


}
