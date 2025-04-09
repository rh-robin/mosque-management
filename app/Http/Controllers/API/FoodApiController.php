<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\FoodInfo;
use App\Models\Pet;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FoodApiController extends Controller
{
    use ResponseTrait;
    public function analyzeFood(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(),[
            'image' => 'required|image|mimes:jpg,jpeg,png,gif',
            'pet_id' => 'required|integer|exists:pets,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        //$pet = Pet::findOrFail($request->pet_id);


        // Get image file
        $image = $request->file('image');

        // Convert image to base64
        $imageBase64 = base64_encode(file_get_contents($image->getRealPath()));

        // Send a request to the OpenAI API
        $apiKey = config('services.openAi.api_key');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert nutritionist and food analyst.'],
                ['role' => 'user', 'content' => [
                    ['type' => 'text', 'text' => 'Analyze this food image and return the following details in JSON format under the variable "EstimatedNutritionalInformation":'],
                    ['type' => 'text', 'text' => '{
                    "isFood": "yes or no",
                    "name": "just name of the food without any pets or other words",
                    "weight": "estimated weight of the food in grams",
                    "calorie": "estimated calorie content in kcal",
                    "exercise_time": "estimated exercise_time in minutes to burn the calories",
                    "protein": "estimated protein content in grams",
                    "carbs": "estimated carbohydrate content in grams",
                    "fat": "estimated fat content in grams"
                }'],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:image/{$image->getClientOriginalExtension()};base64," . $imageBase64]]
                ]],
            ],
            'max_tokens' => 300
        ]);

        // Return the response
        // Parse and clean the response
        $nutritionInfo = $response->json('choices.0.message.content');

        // Remove the markdown formatting (```json\n and \n```), then decode the JSON
        $cleanedNutritionInfo = json_decode(trim(str_replace(["```json\n", "\n```"], '', $nutritionInfo)), true);

        // Check if the data is valid
        if (!isset($cleanedNutritionInfo['EstimatedNutritionalInformation'])) {
            return $this->sendError('Invalid response format from AI', [], 500);
        }else{
            if($cleanedNutritionInfo['EstimatedNutritionalInformation']['isFood'] == 'no'){
                return $this->sendError('This is not a food', [], 500);
            }
            $foodInfo = new FoodInfo();
            $foodInfo->pet_id = $request->pet_id;
            $foodInfo->name = $cleanedNutritionInfo['EstimatedNutritionalInformation']['name'];
            $foodInfo->weight = $cleanedNutritionInfo['EstimatedNutritionalInformation']['weight'];
            $foodInfo->calorie = $cleanedNutritionInfo['EstimatedNutritionalInformation']['calorie'];
            $foodInfo->exercise_time = $cleanedNutritionInfo['EstimatedNutritionalInformation']['exercise_time'];
            $foodInfo->protein = $cleanedNutritionInfo['EstimatedNutritionalInformation']['protein'];
            $foodInfo->carbs = $cleanedNutritionInfo['EstimatedNutritionalInformation']['carbs'];
            $foodInfo->fat = $cleanedNutritionInfo['EstimatedNutritionalInformation']['fat'];
            $file = 'image';
            if ($request->hasFile($file)) {
                // Upload the new file
                $randomString = Str::random(10);
                $foodInfo->image  = Helper::fileUpload($request->file($file), 'food', $randomString);
            }
            $foodInfo->save();
        }
        $message = 'food uploaded to chatgpt successfully!';
        return $this->sendResponse($cleanedNutritionInfo['EstimatedNutritionalInformation'], $message, '', 201);
    }



    /* ============================== CLAUDE AI ======================= */
    public function analyzeFoodClaude(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(),[
            'image' => 'required|image|mimes:jpg,jpeg,png,gif',
            //'pet_id' => 'required|integer|exists:pets,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        // Get image file
        $image = $request->file('image');

        // Convert image to base64
        $imageBase64 = base64_encode(file_get_contents($image->getRealPath()));

        // Get the correct MIME type for the image
        $extension = $image->getClientOriginalExtension();
        $mimeType = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ][$extension] ?? $image->getMimeType();

        // Send a request to the Claude API
        $apiKey = config('services.claudeAi.api_key');

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-7-sonnet-20250219', // Using Claude 3.7 Sonnet as it supports multimodal features
            'max_tokens' => 300,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "Analyze this food image for pet or human and return the following details in JSON format under the variable \"EstimatedNutritionalInformation\" and no extra text. Put null if the image does not contain food.:
                    {
                        \"isFood\": \"yes or no\",
                        \"name\": \"just name of the food without any pets or other words\",
                        \"weight\": \"estimated weight of the food in grams\",
                        \"calorie\": \"estimated calorie content in kcal\",
                        \"exercise_time\": \"estimated exercise_time in minutes to burn the calories\",
                        \"protein\": \"estimated protein content in grams\",
                        \"carbs\": \"estimated carbohydrate content in grams\",
                        \"fat\": \"estimated fat content in grams\"
                    }"
                        ],
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mimeType,
                                'data' => $imageBase64
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // Parse the Claude API response
        $responseData = $response->json();

        $message = 'Food analyzed by Claude AI successfully!';
        $nutritionInfo = [];

        /*if (!empty($responseData['content'][0]['text'])) {
            // Extract the JSON part from the text
            preg_match('/```json\n(.*?)\n```/s', $responseData['content'][0]['text'], $matches);

            if (!empty($matches[1])) {
                $nutritionInfo = json_decode($matches[1], true);
            }
        }*/


        //return response()->json($nutritionInfo['EstimatedNutritionalInformation'], 200);
        return $this->sendResponse($responseData, $message, '', 201);

    }


    public function getFoodInfoByDate(Request $request){
        // Validate the request
        $validator = Validator::make($request->all(),[
            'date' => 'required|date_format:d/m/Y',
            'pet_id' => 'required|integer|exists:pets,id'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }

        $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');

        $data = FoodInfo::where('pet_id', $request->pet_id)
            ->whereDate('created_at', $date)
            ->get()
            ->map(function ($food) {
                return [
                    'id' => $food->id,
                    'pet_id' => $food->pet_id,
                    'name' => $food->name,
                    'image' => $food->image,
                    'calorie' => $food->calorie,
                    'exercise_time' => $food->exercise_time,
                    'protein' => $food->protein,
                    'carbs' => $food->carbs,
                    'fat' => $food->fat,
                    'weight' => $food->weight,
                    'time' => Carbon::parse($food->created_at)->format('h:i A'), // Format time
                ];
            });

        // Calculate total values
        $total_calorie = $data->sum('calorie');
        $total_protein = $data->sum('protein');
        $total_carbs = $data->sum('carbs');
        $total_fat = $data->sum('fat');
        $total_exercise_time = $data->sum('exercise_time');

        // Add totals to response
        $response = [
            'food_data' => $data,
            'total_calorie' => $total_calorie,
            'total_protein' => $total_protein,
            'total_carbs' => $total_carbs,
            'total_fat' => $total_fat,
            'total_exercise_time' => $total_exercise_time,
        ];

        $message = 'Food data for date: ' . $date . '.';
        return $this->sendResponse($response, $message, '', 200);
    }






}
