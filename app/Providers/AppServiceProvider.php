<?php

namespace App\Providers;

use App\Models\Product;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Paginator::useBootstrapFive();
    }
}
