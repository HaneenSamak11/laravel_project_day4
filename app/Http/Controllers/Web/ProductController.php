<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('products.create');
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        Gate::authorize('view', $product);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('products.edit', compact('product'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
