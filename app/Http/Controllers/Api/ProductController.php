<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'min_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'in_stock' => ['sometimes', 'nullable', 'boolean'],
            'sort_by' => ['sometimes', 'in:id,name,price,quantity,created_at'],
            'direction' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(isset($validated['min_price']), fn ($query) => $query->where('price', '>=', $validated['min_price']))
            ->when(isset($validated['max_price']), fn ($query) => $query->where('price', '<=', $validated['max_price']))
            ->when(isset($validated['in_stock']), function ($query) use ($validated): void {
                $validated['in_stock']
                    ? $query->where('quantity', '>', 0)
                    : $query->where('quantity', 0);
            })
            ->orderBy($validated['sort_by'] ?? 'created_at', $validated['direction'] ?? 'desc')
            ->paginate($validated['per_page'] ?? 15);

        return ProductResource::collection($products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        Gate::authorize('view', $product);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product->fresh()),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        Gate::authorize('delete', $product);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
