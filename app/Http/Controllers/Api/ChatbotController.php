<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Models\ChatMessage;
use App\Services\OpenAIDataChatbot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotController extends Controller
{
    public function history(Request $request): JsonResponse
    {
        $messages = $request->user()->chatMessages()
            ->latest()
            ->paginate(30);

        return response()->json(['data' => $messages]);
    }

    public function ask(ChatRequest $request, OpenAIDataChatbot $chatbot): JsonResponse
    {
        $user = $request->user();
        $history = $user->chatMessages()->latest()->limit(10)->get()->reverse()->values();
        $message = $request->validated('message');

        $userMessage = ChatMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $message,
        ]);

        try {
            $result = $chatbot->ask($user, $message, $history);
        } catch (Throwable $exception) {
            Log::error('API chatbot failed', ['exception' => $exception]);
            $userMessage->delete();

            return response()->json([
                'message' => 'The chatbot request failed.',
                'error' => app()->isLocal() ? $exception->getMessage() : 'Check the server log and OpenAI configuration.',
            ], 502);
        }

        $assistantMessage = ChatMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $result['answer'],
            'metadata' => [
                'model' => $result['model'],
                'tools_used' => $result['tools_used'],
            ],
        ]);

        return response()->json([
            'message' => 'Chatbot answered successfully.',
            'data' => [
                'answer' => $assistantMessage->content,
                'model' => $result['model'],
                'tools_used' => $result['tools_used'],
                'message_id' => $assistantMessage->id,
            ],
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $request->user()->chatMessages()->delete();

        return response()->json(['message' => 'Chat history cleared.']);
    }
}
