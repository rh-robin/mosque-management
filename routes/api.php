<?php

use App\Http\Controllers\API\BreedsApiController;
use App\Http\Controllers\API\FoodApiController;
use App\Http\Controllers\API\PetApiController;
use App\Http\Controllers\API\SocialLoginController;
use App\Http\Controllers\API\SubscriptionController;
use App\Http\Controllers\API\TipsCareApiController;
use App\Http\Controllers\API\WeightApiController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::post('/socialLogin', [SocialLoginController::class, 'SocialLogin']);

Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [SocialLoginController::class, 'logout']);
    Route::get('/profile', [SocialLoginController::class, 'getProfile']);

    /* ==========  pet api ==========*/
    Route::post('/pet/store', [PetApiController::class, 'store']);
    Route::post('/pet/update/{id}', [PetApiController::class, 'update']);
    Route::get('/my-pet', [PetApiController::class, 'myPet']);
    Route::post('/select-pet', [PetApiController::class, 'selectPet']);
    Route::get('/selected-pet', [PetApiController::class, 'selectedPet']);
});

/*================= food api ==================*/
Route::post('/analyze-food', [FoodApiController::class, 'analyzeFood']);
Route::post('/food-info/date', [FoodApiController::class, 'getFoodInfoByDate']);
Route::post('/analyze-food/claude', [FoodApiController::class, 'analyzeFoodClaude']);

/* ====================== WEIGHT API =====================*/
Route::post('/weight/store', [WeightApiController::class, 'storeWeight']);
Route::get('/pet-weight/this-week/{pet_id}', [WeightApiController::class, 'petWeightThisWeek']);
Route::get('/pet-weight/this-month/{pet_id}', [WeightApiController::class, 'petWeightThisMonth']);
Route::get('/pet-weight/six-month/{pet_id}', [WeightApiController::class, 'petWeightSixMonth']);

//food weight =====
Route::get('/food-weight/today/{pet_id}', [WeightApiController::class, 'foodWeightToday']);
Route::get('/food-weight/this-week/{pet_id}', [WeightApiController::class, 'foodWeightThisWeek']);
Route::get('/food-weight/five-months/{pet_id}', [WeightApiController::class, 'foodWeightFiveMonths']);



/* ====================== TIPS CARE api ===================*/
Route::get('/tips-and-care', [TipsCareApiController::class, 'index']);


/* ====================== BREED API ===================*/
Route::get('/breeds', [BreedsApiController::class, 'index']);
Route::get('/fetch-breeds/cat', [BreedsApiController::class, 'catBreeds']);
Route::get('/fetch-breeds/dog', [BreedsApiController::class, 'dogBreeds']);

Route::get('/terms-conditions', [TipsCareApiController::class, 'terms']);
Route::get('/privacy-policy', [TipsCareApiController::class, 'policy']);

/*================= Subscriptions APIS ==================*/
Route::middleware(['auth:api'])->group(function () {
    Route::post('create-subscription', [SubscriptionController::class, 'createSubscription']);
    Route::post('cancel-subscription', [SubscriptionController::class, 'cancelSubscription']);
});
// Subscription plans
Route::get('subscription-plans', [SubscriptionController::class, 'getPlans']);

Route::any('checkout/success', [SubscriptionController::class, 'checkoutSuccess'])->name('checkout.success');
Route::get('checkout/cancel', [SubscriptionController::class, 'checkoutCancel'])->name('checkout.cancel');
