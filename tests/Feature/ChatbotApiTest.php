<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatbotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_uses_a_database_tool_and_returns_answer(): void
    {
        config(['services.openai.key' => 'test-key']);
        Product::factory()->count(2)->create();
        Sanctum::actingAs(User::factory()->create());

        Http::fakeSequence()
            ->push([
                'model' => 'gpt-5.6-luna',
                'output' => [[
                    'id' => 'fc_1',
                    'type' => 'function_call',
                    'call_id' => 'call_1',
                    'name' => 'get_product_statistics',
                    'arguments' => '{}',
                ]],
            ])
            ->push([
                'model' => 'gpt-5.6-luna',
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => 'There are 2 products.',
                    ]],
                ]],
            ]);

        $this->postJson('/api/chatbot/ask', [
            'message' => 'How many products are there?',
        ])->assertOk()->assertJsonPath('data.answer', 'There are 2 products.');
    }
}
