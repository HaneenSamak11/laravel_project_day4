<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'nullable', 'in:admin,user'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->latest()
            ->paginate($validated['per_page'] ?? 15);

        return UserResource::collection($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => new UserResource($user)]);
    }

    public function updateRole(UserRoleRequest $request, User $user): JsonResponse
    {
        if ($request->user()->is($user) && $request->validated('role') !== 'admin') {
            throw ValidationException::withMessages([
                'role' => ['You cannot remove your own admin role.'],
            ]);
        }

        $user->update(['role' => $request->validated('role')]);

        return response()->json([
            'message' => 'User role updated.',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account from this endpoint.'],
            ]);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
