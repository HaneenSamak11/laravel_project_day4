<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ProjectDataTools
{
    public function definitions(User $user): array
    {
        $tools = [
            [
                'type' => 'function',
                'name' => 'get_database_overview',
                'description' => 'Get a high-level overview of accessible project data and record counts.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                    'required' => [],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ],
            [
                'type' => 'function',
                'name' => 'get_product_statistics',
                'description' => 'Get product totals, stock totals, inventory value, average price, minimum price, and maximum price.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                    'required' => [],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ],
            [
                'type' => 'function',
                'name' => 'search_products',
                'description' => 'Search and filter products by name, description, price, stock, and sorting.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => ['string', 'null']],
                        'min_price' => ['type' => ['number', 'null']],
                        'max_price' => ['type' => ['number', 'null']],
                        'min_quantity' => ['type' => ['integer', 'null']],
                        'max_quantity' => ['type' => ['integer', 'null']],
                        'sort_by' => ['type' => 'string', 'enum' => ['id', 'name', 'price', 'quantity', 'created_at']],
                        'direction' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
                    ],
                    'required' => ['query', 'min_price', 'max_price', 'min_quantity', 'max_quantity', 'sort_by', 'direction', 'limit'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ],
            [
                'type' => 'function',
                'name' => 'get_product_by_id',
                'description' => 'Get one product using its numeric database ID.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'minimum' => 1],
                    ],
                    'required' => ['id'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ],
        ];

        if ($user->isAdmin()) {
            $tools[] = [
                'type' => 'function',
                'name' => 'get_user_statistics',
                'description' => 'Admin-only user counts grouped by role. Does not expose passwords or tokens.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                    'required' => [],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
            $tools[] = [
                'type' => 'function',
                'name' => 'search_users',
                'description' => 'Admin-only search for users by name and role. Returns safe profile fields only.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => ['string', 'null']],
                        'role' => ['type' => ['string', 'null'], 'enum' => ['admin', 'user', null]],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30],
                    ],
                    'required' => ['query', 'role', 'limit'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        return $tools;
    }

    public function execute(string $name, array $arguments, User $user): array
    {
        return match ($name) {
            'get_database_overview' => $this->databaseOverview($user),
            'get_product_statistics' => $this->productStatistics(),
            'search_products' => $this->searchProducts($arguments),
            'get_product_by_id' => $this->getProductById($arguments),
            'get_user_statistics' => $this->userStatistics($user),
            'search_users' => $this->searchUsers($arguments, $user),
            default => throw new InvalidArgumentException("Unknown data tool: {$name}"),
        };
    }

    private function databaseOverview(User $user): array
    {
        $overview = [
            'accessible_tables' => ['products'],
            'products_count' => Product::query()->count(),
        ];

        if ($user->isAdmin()) {
            $overview['accessible_tables'][] = 'users';
            $overview['users_count'] = User::query()->count();
        }

        return $overview;
    }

    private function productStatistics(): array
    {
        $stats = Product::query()
            ->selectRaw('COUNT(*) as products_count')
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_units')
            ->selectRaw('COALESCE(SUM(price * quantity), 0) as inventory_value')
            ->selectRaw('COALESCE(AVG(price), 0) as average_price')
            ->selectRaw('COALESCE(MIN(price), 0) as minimum_price')
            ->selectRaw('COALESCE(MAX(price), 0) as maximum_price')
            ->first();

        return [
            'products_count' => (int) $stats->products_count,
            'total_units' => (int) $stats->total_units,
            'inventory_value' => round((float) $stats->inventory_value, 2),
            'average_price' => round((float) $stats->average_price, 2),
            'minimum_price' => round((float) $stats->minimum_price, 2),
            'maximum_price' => round((float) $stats->maximum_price, 2),
            'out_of_stock_count' => Product::query()->where('quantity', 0)->count(),
            'low_stock_count' => Product::query()->whereBetween('quantity', [1, 5])->count(),
        ];
    }

    private function searchProducts(array $arguments): array
    {
        $query = Product::query();

        $this->applyProductFilters($query, $arguments);

        $sortBy = in_array($arguments['sort_by'] ?? '', ['id', 'name', 'price', 'quantity', 'created_at'], true)
            ? $arguments['sort_by']
            : 'created_at';
        $direction = ($arguments['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $limit = max(1, min((int) ($arguments['limit'] ?? 10), 50));

        return [
            'count_returned' => $query->count() > $limit ? $limit : $query->count(),
            'records' => $query
                ->orderBy($sortBy, $direction)
                ->limit($limit)
                ->get(['id', 'name', 'description', 'price', 'quantity', 'created_at'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => (float) $product->price,
                    'quantity' => $product->quantity,
                    'in_stock' => $product->quantity > 0,
                    'created_at' => $product->created_at?->toDateTimeString(),
                ])
                ->all(),
        ];
    }

    private function applyProductFilters(Builder $query, array $arguments): void
    {
        if (filled($arguments['query'] ?? null)) {
            $search = trim((string) $arguments['query']);
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        foreach (['min_price' => '>=', 'max_price' => '<=', 'min_quantity' => '>=', 'max_quantity' => '<='] as $key => $operator) {
            if (($arguments[$key] ?? null) !== null) {
                $column = str_contains($key, 'price') ? 'price' : 'quantity';
                $query->where($column, $operator, $arguments[$key]);
            }
        }
    }

    private function getProductById(array $arguments): array
    {
        $product = Product::query()->find($arguments['id'] ?? 0);

        if (! $product) {
            return ['found' => false, 'message' => 'Product not found.'];
        }

        return [
            'found' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'quantity' => $product->quantity,
                'in_stock' => $product->quantity > 0,
                'created_at' => $product->created_at?->toDateTimeString(),
                'updated_at' => $product->updated_at?->toDateTimeString(),
            ],
        ];
    }

    private function userStatistics(User $requestingUser): array
    {
        $this->requireAdmin($requestingUser);

        return [
            'users_count' => User::query()->count(),
            'admins_count' => User::query()->where('role', 'admin')->count(),
            'normal_users_count' => User::query()->where('role', 'user')->count(),
            'new_users_last_30_days' => User::query()->where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    private function searchUsers(array $arguments, User $requestingUser): array
    {
        $this->requireAdmin($requestingUser);

        $query = User::query();

        if (filled($arguments['query'] ?? null)) {
            $query->where('name', 'like', '%'.trim((string) $arguments['query']).'%');
        }

        if (in_array($arguments['role'] ?? null, ['admin', 'user'], true)) {
            $query->where('role', $arguments['role']);
        }

        $limit = max(1, min((int) ($arguments['limit'] ?? 10), 30));

        return [
            'records' => $query->latest()->limit($limit)->get(['id', 'name', 'role', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'created_at' => $user->created_at?->toDateTimeString(),
                ])->all(),
        ];
    }

    private function requireAdmin(User $user): void
    {
        if (! $user->isAdmin()) {
            throw new InvalidArgumentException('This data tool requires an admin user.');
        }
    }
}
