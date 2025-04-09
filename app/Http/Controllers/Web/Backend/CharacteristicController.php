<?php

namespace App\Http\Controllers\Web\Backend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Breed;
use App\Models\Characteristic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Str;

class CharacteristicController extends Controller
{
    public function create()
    {
        $breeds = Breed::all();
        return view('backend.layouts.characteristics.create', compact('breeds'));
    }


    public function createOrUpdate(Request $request)
    {
        // ✅ Define sections dynamically
        $sections = [
            "physical_characteristics", "behavior_and_temperament", "food_and_diet",
            "health_conditions", "lifespan", "grooming", "conclusion"
        ];

        // ✅ Validate the incoming request
        $rules = [
            'breed' => 'required|exists:breeds,id',
        ];

        foreach ($sections as $section) {
            $rules["{$section}_image"] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg';
            $rules["{$section}_content"] = 'nullable|string';
        }

        $rulesMessage = [
            'breed.required' => 'The breed field is required.',
        ];

        $request->validate($rules, $rulesMessage);

        // 🗂️ Retrieve the breed
        $breed = Breed::findOrFail($request->breed);

        try {
            //DB::beginTransaction(); // Start the transaction

            foreach ($sections as $section) {
                // 🏷️ Prepare data for insertion or update
                $data = [
                    'breed_id' => $breed->id,
                    'title' => $section,
                    'content' => $request->input("{$section}_content") ?? null,
                ];

                // 📤 Handle image upload if present
                if ($request->hasFile("{$section}_image")) {
                    // Check if there's an old image associated with the characteristic
                    $characteristic = Characteristic::where('breed_id', $breed->id)
                        ->where('title', $section)
                        ->first();

                    if ($characteristic && $characteristic->image) {
                        // Delete the old image from the server
                        if (file_exists($characteristic->image)) {
                            Helper::fileDelete($characteristic->image);
                        }
                    }

                    // Generate random string for the new image
                    $randomString = Str::random(10);

                    // Upload the new image
                    $data['image'] = Helper::fileUpload($request->file("{$section}_image"), 'characteristics', $randomString);
                }


                // 💾 Update or create characteristic
                Characteristic::updateOrCreate(
                    ['breed_id' => $breed->id, 'title' => $data['title']],
                    $data
                );
            }

            //DB::commit(); // ✅ Commit the transaction if all is successful

            return response()->json(['success' => true, 'message' => 'Characteristics updated successfully.']);
        } catch (\Exception $e) {
            //DB::rollBack(); // ❌ Rollback the transaction in case of an error
            return response()->json(['errors' => true, 'message' => 'Characteristics failed to update']);
        }
    }



    public function fetchCharacteristics(Request $request)
    {
        $breedId = $request->breed_id;

        // Fetch characteristics for the selected breed
        $characteristics = Characteristic::where('breed_id', $breedId)->get()->keyBy('title');

        // Define expected sections to ensure correct mapping
        $sections = [
            "Physical Characteristics",
            "Behavior and Temperament",
            "Food and Diet",
            "Health Conditions",
            "Lifespan",
            "Grooming",
            "Conclusion"
        ];

        // Prepare response data
        $response = [];
        foreach ($sections as $section) {
            $response[Str::snake($section)] = $characteristics[Str::snake($section)] ?? null;
        }

        return response()->json([
            'success' => true,
            'characteristics' => $response
        ]);
    }



}
