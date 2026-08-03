<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Normal User',
                'password' => 'password',
                'role' => 'user',
            ]
        );

        if (Product::query()->doesntExist()) {
            Product::factory()->count(10)->create();
        }
    }
}
