<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Services\TwitterService;
use Illuminate\Http\Request;

class TwitterController extends Controller
{
    protected $twitterService;

    public function __construct(TwitterService $twitterService)
    {
        $this->twitterService = $twitterService;
    }

    // Reply to mentions
    public function replyMentions()
    {
        $mentions = $this->twitterService->twitter->get('statuses/mentions_timeline', ['count' => 10]);

        foreach ($mentions as $mention) {
            $tweetId = $mention->id_str;
            $username = $mention->user->screen_name;
            $this->twitterService->replyToMention($tweetId, $username);
        }

        return response()->json(['message' => 'Replied to mentions']);
    }

    // Send Direct Messages to new followers
    public function sendDMs()
    {
        $followers = $this->twitterService->twitter->get('followers/list', ['count' => 10]);

        foreach ($followers->users as $user) {
            $userId = $user->id_str;
            $username = $user->screen_name;
            $this->twitterService->sendDirectMessage($userId, $username);
        }

        return response()->json(['message' => 'DMs sent']);
    }
}
