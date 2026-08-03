<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'productCount' => Product::query()->count(),
            'lowStockCount' => Product::query()->where('quantity', '<=', 5)->count(),
            'inventoryValue' => (float) Product::query()->selectRaw('COALESCE(SUM(price * quantity), 0) as total')->value('total'),
            'userCount' => auth()->user()->isAdmin() ? User::query()->count() : null,
        ]);
    }
}
