<?php
use App\Http\Controllers\Web\Backend\AdminController;
use App\Http\Controllers\Web\Backend\BreedController;
use App\Http\Controllers\Web\Backend\CharacteristicController;
use App\Http\Controllers\Web\Backend\TipsCareController;
use App\Http\Controllers\Web\Backend\TwitterController;
use App\Services\TwitterService;
use Illuminate\Support\Facades\Route;

use Abraham\TwitterOAuth\TwitterOAuth;


Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        /*============ tips and care routes ==========*/
        Route::prefix('tips-and-care')
            ->name('tips_care.')
            ->controller(TipsCareController::class)
            ->group(function () {
                Route::get('/index', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{id}', 'edit')->name('edit');
                Route::post('/update/{id}', 'update')->name('update');
                Route::delete('/destroy/{id}', 'destroy')->name('destroy');
        });

        /*============ breed routes ==========*/
        Route::prefix('breed')
            ->name('breed.')
            ->controller(BreedController::class)
            ->group(function () {
                Route::get('/index', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{id}', 'edit')->name('edit');
                Route::post('/update/{id}', 'update')->name('update');
                Route::delete('/destroy/{id}', 'destroy')->name('destroy');
            });


        /*============ characteristic routes ==========*/
        Route::prefix('breed/characteristic')
            ->name('breed.characteristic.')
            ->controller(CharacteristicController::class)
            ->group(function () {
                Route::get('/create', 'create')->name('create');
                Route::get('/fetch', 'fetchCharacteristics')->name('fetch');
                Route::post('/crate-update', 'createOrUpdate')->name('createOrUpdate');
            });


});

/*================ TWITTER BOT ============*/
Route::get('/twitter/reply-mentions', [TwitterController::class, 'replyMentions']);
Route::get('/twitter/send-dms', [TwitterController::class, 'sendDMs']);

Route::get('/get-user-id/{username}', function ($username, TwitterService $twitterService) {
    //dd($username);
    $userId = $twitterService->getUserIdByUsername($username);
    return response()->json(['username' => $username, 'user_id' => $userId]);
});


Route::get('/test-reply', function (TwitterService $twitterService) {
    $tweetId = '1234567890123456789'; // Replace with a real Tweet ID
    $username = 'example_user'; // Replace with the Twitter username of the tweet author

    $response = $twitterService->replyToMention($tweetId, $username);
    return response()->json($response);
});

Route::get('/test-dm', function (TwitterService $twitterService) {
    $userId = '1898598113235075074'; // Replace with a real Twitter user ID
    $username = 'Mamon12209'; // Replace with the Twitter username

    $response = $twitterService->sendDirectMessage($userId, $username);
    return response()->json($response);
});



Route::get('/send-dm', function () {

    $participant_id = '';  // Replace with the recipient's Twitter user ID
    $consumer_key = ''; // Replace with your consumer key
    $consumer_secret = ''; // Replace with your consumer secret
    $oauth_token = ''; // Replace with your OAuth token
    $oauth_token_secret = ''; // Replace with your OAuth token secret

    // Initialize TwitterOAuth with OAuth credentials
    $oauth = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
    $oauth->setApiVersion('2'); // Ensure you're using v2 of the Twitter API

    // Prepare the message to send in DM
    $message = 'Hello, this is a test message from the API!';

    // Send the POST request to Twitter API v2 to send a DM
    $response = $oauth->post("dm_conversations/with/{$participant_id}/messages", [
        'text' => $message // The message text you want to send
    ]);

    // Log the response
    \Log::info('Twitter DM API Response:', (array) $response);

    // Check if there are any errors in the response
    if (isset($response->errors)) {
        return response()->json(['error' => $response->errors]);
    }

    // Return success response
    return response()->json(['success' => 'DM sent successfully', 'response' => $response]);
});





Route::get('/test-twitter', function (TwitterService $twitterService) {

    $consumer_key = '';
    $consumer_secret = '';
    $oauth_token = '';
    $oauth_token_secret = '';

    $connection = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);
    $content = $connection->get("users/me");
    //return response()->json($content);
    //return $twitterService->testTwitterConnection();


    $recipient_id = '';  // The recipient's user ID

    // Create a new connection with TwitterOAuth
    $connection = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret);

    // Set the API version to v2 (optional)
    $connection->setApiVersion('2');

    $message_text = "Hello! This is a test direct message from my Laravel app. 🚀";

    // Twitter API v2 endpoint to send a DM
    $response = $connection->post("dm_conversations", [
        "event" => [
            "message_create" => [
                "target" => ["recipient_id" => $recipient_id],
                "message_data" => ["text" => $message_text]
            ]
        ]
    ]);

    // Check if the request was successful
    if (isset($response->errors)) {
        return response()->json(['error' => $response->errors]);
    }

    return response()->json([
        'message' => 'DM sent successfully!',
        'response' => $response
    ]);
});


