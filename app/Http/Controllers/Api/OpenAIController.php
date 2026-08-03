<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenAIController extends Controller
{
    public function ask(): JsonResponse
    {
        return response()->json([
            'message' => 'This endpoint was replaced by POST /api/chatbot/ask.',
        ], 410);
    }
}
