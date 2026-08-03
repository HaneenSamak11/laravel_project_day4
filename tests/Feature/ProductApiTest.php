<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_products(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Product::factory()->count(3)->create();

        $this->getJson('/api/products')->assertOk();
    }

    public function test_normal_user_cannot_create_product(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $this->postJson('/api/products', [
            'name' => 'Laptop',
            'price' => 1000,
            'quantity' => 2,
        ])->assertForbidden();
    }

    public function test_admin_can_create_product(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/products', [
            'name' => 'Laptop',
            'price' => 1000,
            'quantity' => 2,
        ])->assertCreated();
    }
}
