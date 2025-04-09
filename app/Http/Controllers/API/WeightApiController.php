<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FoodInfo;
use App\Models\Weight;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WeightApiController extends Controller
{
    use ResponseTrait;

    public function storeWeight(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(),[
            'pet_id' => 'required|exists:pets,id',
            'current_weight' => 'required|numeric',
            'weight_goal' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', $validator->errors()->toArray(), 422);
        }
        //dd(today());

        // Get the validated data
        $validatedData = $validator->validated();


        try {
            $weight = Weight::where('pet_id', $validatedData['pet_id'])->whereDate('updated_at', today())->first();
            //dd($weight);
            if ($weight) {
                //dd($validatedData['current_weight']);
                $weight->current_weight =  $validatedData['current_weight'];
                $weight->weight_goal = $validatedData['weight_goal'];
                $weight->save();

            }else{
                $weight = new Weight();
                $weight->pet_id = $validatedData['pet_id'];
                $weight->current_weight =  $validatedData['current_weight'];
                $weight->weight_goal = $validatedData['weight_goal'];
                $weight->save();
            }

            $message = 'Weight data saved successfully.';

            return $this->sendResponse($weight, $message, '', 201);
        }catch (\Exception $exception){
            return $this->sendError($exception->getMessage(), [], $exception->getCode());
        }

    }

    /*public function getWeightByWeeks($pet_id) {
        $startDate = now()->startOfWeek(); // Start of the current week
        $sixWeeksAgo = $startDate->copy()->subWeeks(5); // Get date 6 weeks ago

        // Get weight records for the last 6 weeks sorted by updated_at
        $weights = Weight::where('pet_id', $pet_id)
            ->where('updated_at', '>=', $sixWeeksAgo)
            ->orderBy('updated_at', 'asc')
            ->get(['current_weight', 'updated_at', 'weight_goal']);

        // If no data is found, return an empty response
        if ($weights->isEmpty()) {
            return $this->sendResponse(['weights' => [], 'weight_goal' => null], 'No weight data found.', '', 200);
        }

        $weeklyData = [];
        $lastWeight = null; // Store the last known weight

        // Loop through the last 6 weeks (every 7 days)
        for ($i = 0; $i < 6; $i++) {
            $weekDate = $sixWeeksAgo->copy()->addDays($i * 7); // Increment by 7 days

            // Get the last weight recorded up to this week
            $weekWeight = $weights->where('updated_at', '<=', $weekDate->endOfWeek())->last();

            // If no weight data for this week, use the last known weight
            if (!$weekWeight && $lastWeight) {
                $weekWeight = $lastWeight;
            }

            // Store the last known weight for fallback in the next iteration
            if ($weekWeight) {
                $lastWeight = $weekWeight;
                $weeklyData[] = [
                    'current_weight' => $weekWeight->current_weight,
                    'date' => $weekDate->format('M j'), // Format: "Mar 1"
                ];
            }
        }

        // Get the latest weight goal from the last record
        $weightGoal = $weights->last()->weight_goal ?? null;

        // Prepare the response
        $response = [
            'weights' => $weeklyData,
            'weight_goal' => $weightGoal,
        ];

        return $this->sendResponse($response, 'Weight data retrieved successfully.', '', 200);
    }*/


    /*================== PET WEIGHT OF THIS WEEK ===============*/
    public function petWeightThisWeek($pet_id){
        $today = now();
        $startDate = $today->subDays(6)->startOfDay(); // 7-day range starting from last Sunday

        // Fetch weights within the last 7 days, ordered by date
        $weights = Weight::where('pet_id', $pet_id)
            ->where('updated_at', '>=', $startDate)
            ->orderBy('updated_at', 'asc')
            ->get(['current_weight', 'updated_at', 'weight_goal']);

        // Initialize default data structure with day names
        $weekData = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = now()->subDays(6 - $i); // Iterate from 6 days ago to today
            $weekData->put($date->format('l'), null);
        }

        // Fill available weight data into the week structure
        $lastWeight = null;
        foreach ($weights as $weight) {
            $dayName = $weight->updated_at->format('l');
            $lastWeight = $weight->current_weight;
            $weekData[$dayName] = $lastWeight;
        }

        // Fill missing days with the previous day's weight
        $previousWeight = null;
        foreach ($weekData as $day => $value) {
            if ($value === null) {
                $weekData[$day] = $previousWeight;
            } else {
                $previousWeight = $value;
            }
        }

        // Get last recorded weight goal within the 7-day range
        $weightGoal = $weights->last()->weight_goal ?? null;

        // Prepare response
        $response = [
            'weights' => $weekData->filter()->map(fn($weight, $day) => [
                'day' => $day,
                'current_weight' => $weight,
            ])->values(), // Remove null values and reset keys
            'weight_goal' => $weightGoal
        ];

        return $this->sendResponse($response, 'Weight data of this week retrieved successfully.', '', 200);
    }
    /*================== END PET WEIGHT OF THIS WEEK ===============*/



    /*================= GET PET WEIGHT OF THIS MONTH (30 days) =================*/
    public function petWeightThisMonth($pet_id)
    {
        $today = now();
        $startDate = $today->subDays(29)->startOfDay(); // Last 30 days including today

        // Fetch weights from the last 30 days, ordered by date
        $weights = Weight::where('pet_id', $pet_id)
            ->where('updated_at', '>=', $startDate)
            ->orderBy('updated_at', 'asc')
            ->get(['current_weight', 'updated_at', 'weight_goal']);

        // Determine step size (every 5 days)
        $dateIntervals = collect();
        for ($i = 5; $i <= 30; $i += 5) {
            $date = now()->subDays(30 - $i); // Get interval dates
            $dateIntervals->put($date->toDateString(), null);
        }

        // Fill available weight data into the selected interval structure
        $lastWeight = null;
        foreach ($weights as $weight) {
            foreach ($dateIntervals as $intervalDate => $value) {
                if ($weight->updated_at->toDateString() <= $intervalDate) {
                    $lastWeight = $weight->current_weight;
                    $dateIntervals[$intervalDate] = $lastWeight;
                }
            }
        }

        // Fill missing dates with the previous available weight
        $previousWeight = null;
        foreach ($dateIntervals as $date => $value) {
            if ($value === null) {
                $dateIntervals[$date] = $previousWeight;
            } else {
                $previousWeight = $value;
            }
        }

        // Filter out empty/null values
        $filteredWeights = $dateIntervals->filter()->map(fn($weight, $date) => [
            'date' => Carbon::parse($date)->format('M j'),
            'current_weight' => $weight,
        ])->values();

        // Get last recorded weight goal within the last 30 days
        $weightGoal = $weights->last()->weight_goal ?? null;

        // Prepare response
        $response = [
            'weights' => $filteredWeights,
            'weight_goal' => $weightGoal
        ];

        return $this->sendResponse($response, 'Weight data of this month retrieved successfully.', '', 200);
    }
    /*================= END GET PET WEIGHT OF THIS MONTH (30 days) =================*/



    /*================= GET PET WEIGHT OF LAST SIX MONTH =================*/
    public function petWeightSixMonth($pet_id)
    {
        $today = now();
        $startDate = $today->subMonths(6)->startOfMonth(); // Last 6 months including current month

        // Fetch weights from the last 6 months, ordered by date
        $weights = Weight::where('pet_id', $pet_id)
            ->where('updated_at', '>=', $startDate)
            ->orderBy('updated_at', 'asc')
            ->get(['current_weight', 'updated_at', 'weight_goal']);

        // Initialize structure for last 6 months
        $monthData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth(); // Get month start dates
            $monthData->put($date->format('M Y'), null);
        }

        // Fill available weight data into the month structure
        $lastWeight = null;
        foreach ($weights as $weight) {
            foreach ($monthData as $month => $value) {
                if ($weight->updated_at->format('M Y') == $month) {
                    $lastWeight = $weight->current_weight;
                    $monthData[$month] = $lastWeight;
                }
            }
        }

        // Fill missing months with the previous available weight
        $previousWeight = null;
        foreach ($monthData as $month => $value) {
            if ($value === null) {
                $monthData[$month] = $previousWeight;
            } else {
                $previousWeight = $value;
            }
        }

        // Filter out empty/null values
        $filteredWeights = $monthData->filter()->map(fn($weight, $month) => [
            'month' => $month,
            'current_weight' => $weight,
        ])->values();

        // Get last recorded weight goal within the last 6 months
        $weightGoal = $weights->last()->weight_goal ?? null;

        // Prepare response
        $response = [
            'weights' => $filteredWeights,
            'weight_goal' => $weightGoal
        ];

        return $this->sendResponse($response, 'Weight data of six months retrieved successfully.', '', 200);
    }
    /*================= END GET PET WEIGHT OF LAST SIX MONTH =================*/



    /*========================== GET FOOD WEIGHT TODAY ========================*/
    public function foodWeightToday($pet_id)
    {
        $today = Carbon::today();

        // Retrieve today's food data for the given pet
        $foods = FoodInfo::where('pet_id', $pet_id)
            ->whereDate('created_at', $today)
            ->get(['created_at', 'weight']);

        // Calculate the total weight
        $totalWeight = $foods->sum('weight');

        // Map each food entry to include formatted time and weight
        $response = $foods->map(function ($food) {
            return [
                'time' => $food->created_at->format('h:i A'), // Format time
                'weight' => $food->weight,
            ];
        });

        // Add the total weight to the response
        return $this->sendResponse(
            [
                'food_details' => $response,
                'total_weight' => $totalWeight,
            ],
            "Today's all food weight and total weight.",
            '',
            200
        );
    }




    /*========================== GET FOOD WEIGHT of THIS WEEK ========================*/
    public function foodWeightThisWeek($pet_id)
    {
        $startDate = Carbon::today()->subDays(6); // Start from 6 days ago
        $endDate = Carbon::today(); // Up to today

        // Fetch all food records in the last 7 days, including today
        $foodData = FoodInfo::where('pet_id', $pet_id)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->get()
            ->groupBy(function ($food) {
                return Carbon::parse($food->created_at)->format('Y-m-d'); // Group by exact date
            });

        // Prepare the response array for the last 7 days
        $response = [];
        $totalWeightForWeek = 0; // Variable to store the total weight of the week

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayName = $date->format('l'); // Day name (Sunday, Monday, etc.)
            $dateString = $date->format('Y-m-d'); // Exact date in format Y-m-d

            // Calculate total weight for the day
            $totalWeightForDay = isset($foodData[$dateString])
                ? $foodData[$dateString]->sum('weight')
                : 0; // If no data, set weight to 0

            // Add the daily weight to the total weekly weight
            $totalWeightForWeek += $totalWeightForDay;

            $response[] = [
                'day' => $dayName,
                'weight' => $totalWeightForDay,
            ];
        }

        // Return the formatted response
        return $this->sendResponse(
            [
                'food_details' => $response,
                'total_weight' => $totalWeightForWeek,
            ],
            "This week's all food weight and total weight.",
            '',
            200
        );
    }




    /*========================== GET FOOD WEIGHT OF LAST FIVE MONTHS ========================*/
    public function foodWeightFiveMonths($pet_id)
    {
        $startDate = Carbon::today()->subMonths(4); // 5 months, including the current month
        $endDate = Carbon::today();

        // Fetch all food records for the last 5 months
        $foodData = FoodInfo::where('pet_id', $pet_id)
            ->whereBetween('created_at', [$startDate->startOfMonth(), $endDate->endOfMonth()])
            ->get()
            ->groupBy(function ($food) {
                return Carbon::parse($food->created_at)->format('Y-m'); // Group by month (Y-m format)
            });

        // Prepare the response array for the last 5 months
        $response = [];
        $totalWeightForFiveMonths = 0; // Variable to store the total weight for the last 5 months

        for ($i = 0; $i < 5; $i++) {
            $date = $startDate->copy()->addMonths($i);
            $monthName = $date->format('F Y'); // Full month name with year (e.g., March 2025)
            $dateString = $date->format('Y-m'); // Exact month in format Y-m

            // Calculate total weight for the month
            $totalWeightForMonth = isset($foodData[$dateString])
                ? $foodData[$dateString]->sum('weight')
                : 0; // If no data, set weight to 0

            // Add the monthly weight to the total weight for the last 5 months
            $totalWeightForFiveMonths += $totalWeightForMonth;

            $response[] = [
                'month' => $monthName,
                'weight' => $totalWeightForMonth,
            ];
        }

        // Return the formatted response with the total weight of the last 5 months
        return $this->sendResponse(
            [
                'food_details' => $response,
                'total_weight' => $totalWeightForFiveMonths,
            ],
            "All food weight of the last five months.",
            null,
            200
        );
    }



}
