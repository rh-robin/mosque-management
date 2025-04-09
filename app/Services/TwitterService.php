<?php

namespace App\Services;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Models\SentMessage;
use Http;

class TwitterService
{
    protected $twitter;

    public function __construct()
    {
        $this->twitter = new TwitterOAuth(
            config('services.twitter.api_key'),
            config('services.twitter.api_secret'),
            config('services.twitter.access_token'),
            config('services.twitter.access_secret')
        );
    }

    // Send Tweet Reply
    public function replyToMention($tweetId, $username)
    {
        $message = "@{$username} Love your furry VIP? 🐶 Join PAWS AI’s exclusive pet community! 3-day free trial 👉 [link]";

        return $this->twitter->post('statuses/update', [
            'status' => $message,
            'in_reply_to_status_id' => $tweetId
        ]);
    }

    // Send Direct Message
    public function sendDirectMessagess($userId, $username)
    {
        /*if (SentMessage::where('user_id', $userId)->exists()) {
            return "Message already sent to {$username}";
        }*/

        $message = "Hey {$username}, ready to connect with pet lovers? Start your 3-day free PAWS AI trial: [link]";

        $payload = [
            'event' => [
                'type' => 'message_create',
                'message_create' => [
                    'target' => ['recipient_id' => $userId],
                    'message_data' => ['text' => $message]
                ]
            ]
        ];

        $response = $this->twitter->post('direct_messages/events/new', $payload);

        // Log the response from Twitter API
        \Log::info('Twitter DM API Response:', (array) $response);

        // Check if the message was successfully sent
        if (isset($response->errors)) {
            return response()->json(['error' => $response->errors]);
        }

        //SentMessage::create(['user_id' => $userId, 'username' => $username]);

        return response()->json(['success' => 'DM sent successfully']);
    }


    public function sendDirectMessage($userId, $username)
    {
        $rateLimitStatus = $this->twitter->get('application/rate_limit_status');
        /*$message = "Hey {$username}, ready to connect with pet lovers? Start your 3-day free PAWS AI trial: [link]";
        $endpoint = "dm_conversations/with/{$userId}/messages";
        $payload = ['text' => $message];

        // Retry logic for rate limiting
        $retries = 3;
        $delay = 1; // Initial delay in seconds

        for ($i = 0; $i < $retries; $i++) {
            $response = $this->twitter->post($endpoint, $payload);
            if ($response->status == 429) {
                \Log::info("Rate limit hit. Retrying in {$delay} seconds...");
                sleep($delay); // Wait before retrying
                $delay *= 2; // Exponential backoff
            } else {
                break; // No error, proceed with the response
            }
        }

        if (isset($response->errors)) {
            return response()->json(['error' => $response->errors]);
        }*/


        $curl = curl_init();

        $participant_id = '1898598113235075074'; // Replace with the recipient's Twitter user ID
        $bearer_token = 'AAAAAAAAAAAAAAAAAAAAAAZlzgEAAAAAypJG6FEmH%2Bsewz6HgcMMr4bq1hw%3D2WA4V51X8c59xPYlMNSIoEPYjzURliZUUxTYIm35wGaRtyrEn3'; // Replace with your actual Bearer token

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.twitter.com/2/dm_conversations/with/{$participant_id}/messages",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'attachments' => [
                    [
                        'media_id' => '1146654567674912769' // Replace with the actual media ID if you want to send an image
                    ]
                ],
                'text' => 'Hello, this is a test message from the API!' // Replace with the message you want to send
            ]),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$bearer_token}",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo "Response: " . $response;
        }

        //return response()->json(['success' => 'DM sent successfully', 'rateLimmit' => $rateLimitStatus]);
    }





    public function getUserIdByUsername($username)
    {
        $username = ltrim($username, '@'); // Remove '@' just in case
        $response = $this->twitter->get("users/by/username/{$username}");

        \Log::info('Twitter API Response:', (array) $response);

        return $response->data->id ?? null;
    }


    public function testTwitterConnection()
    {
        try {
            $response = $this->twitter->get('users/me');

            \Log::info('Twitter API Raw Response:', ['response' => $response]);
            // Check if response is empty
            if (!$response) {
                \Log::error('Twitter API returned an empty response.');
                return response()->json(['error' => 'Twitter API returned an empty response.']);
            }

            // Check if response contains errors
            if (isset($response->errors)) {
                \Log::error('Twitter API Error:', (array) $response->errors);
                return response()->json(['error' => $response->errors]);
            }

            // Log full response
            \Log::info('Twitter API Connection Test:', (array) $response);

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('Twitter API Exception:', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()]);
        }
    }





}
