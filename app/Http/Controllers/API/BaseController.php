<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    /**
     * Send a successful response with an optional token.
     *
     * @param mixed $result
     * @param string $message
     * @param string|null $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse($result, $message, $token = null, $code = 200)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        // If a token is provided, include it in the response
        if ($token) {
            $response['access_token'] = $token;
            $response['token_type'] = 'bearer';
        }

        return response()->json($response, $code);
    }

    /**
     * Send an error response with an optional token.
     *
     * @param string $error
     * @param array $errorMessages
     * @param int $code
     * @param string|null $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError(string $error, array $errorMessages = [], int $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }
}

