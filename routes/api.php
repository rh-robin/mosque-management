<?php

use App\Http\Controllers\API\AdvertisementApiController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BreedsApiController;
use App\Http\Controllers\API\DonationApiController;
use App\Http\Controllers\API\EventApiController;
use App\Http\Controllers\API\FoodApiController;
use App\Http\Controllers\API\PetApiController;
use App\Http\Controllers\API\PostApiController;
use App\Http\Controllers\API\SocialLoginController;
use App\Http\Controllers\API\SubscriptionController;
use App\Http\Controllers\API\TipsCareApiController;
use App\Http\Controllers\API\WeightApiController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

//Route::post('/socialLogin', [SocialLoginController::class, 'SocialLogin']);
/*==================== ALL COMMON ROUTES =================*/
/*Route::middleware(['api.guest'])->group(function () {
    Route::post('user/register', [RegisteredUserController::class, 'userStore']);
    Route::post('admin/register', [RegisteredUserController::class, 'adminStore']);
    Route::post('login', [AuthController::class, 'login']);
});*/
Route::post('user/register', [RegisteredUserController::class, 'userStore']);
Route::post('admin/register', [RegisteredUserController::class, 'adminStore']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/me', [AuthController::class, 'logout']);
});



/*==================== ALL ROUTES FOR USER =================*/
Route::post('user/register', [RegisteredUserController::class, 'userStore']);







/*==================== ALL ROUTES FOR ADMIN =================*/

Route::middleware(['auth:api', 'admin'])
    ->prefix('admin')
    ->group(function () {

    //====== Post API routes
    Route::prefix('post')
        ->controller(PostApiController::class)
        ->group(function () {
        Route::post('/store', 'store');
        Route::post('/update', 'update');
        Route::post('/destroy', 'destroy');
    });

    //====== Event API routes
    Route::prefix('event')
        ->controller(EventApiController::class)
        ->group(function () {
            Route::post('/store', 'store');
            Route::post('/update', 'update');
            Route::post('/destroy', 'destroy');
        });


        //====== Advertisement API routes
        Route::prefix('advertisement')
            ->controller(AdvertisementApiController::class)
            ->group(function () {
                Route::post('/store', 'store');
                Route::post('/update', 'update');
                Route::post('/destroy', 'destroy');
            });


        //====== Advertisement API routes
        Route::prefix('donation')
            ->controller(DonationApiController::class)
            ->group(function () {
                Route::post('/store', 'store');
                Route::post('/update', 'update');
                Route::post('/destroy', 'destroy');
            });
});












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
