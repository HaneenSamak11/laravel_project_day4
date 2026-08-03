<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_open_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Web User',
            'email' => 'web@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
