<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Models\ChatMessage;
use App\Services\OpenAIDataChatbot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class ChatbotController extends Controller
{
    public function index(): View
    {
        $messages = auth()->user()->chatMessages()
            ->latest()
            ->limit(40)
            ->get()
            ->reverse()
            ->values();

        return view('chatbot.index', compact('messages'));
    }

    public function ask(ChatRequest $request, OpenAIDataChatbot $chatbot): RedirectResponse
    {
        $user = $request->user();
        $history = $user->chatMessages()->latest()->limit(10)->get()->reverse()->values();
        $message = $request->validated('message');

        ChatMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $message,
        ]);

        try {
            $result = $chatbot->ask($user, $message, $history);

            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => $result['answer'],
                'metadata' => [
                    'model' => $result['model'],
                    'tools_used' => $result['tools_used'],
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Web chatbot failed', ['exception' => $exception]);

            return back()->withErrors([
                'message' => 'The chatbot could not answer. Check OPENAI_API_KEY and the server log.',
            ]);
        }

        return redirect()->route('chatbot.index');
    }

    public function clear(): RedirectResponse
    {
        auth()->user()->chatMessages()->delete();

        return redirect()->route('chatbot.index')
            ->with('success', 'Chat history cleared.');
    }
}
