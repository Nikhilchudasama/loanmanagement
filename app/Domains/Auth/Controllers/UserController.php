<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Requests\StoreUserRequest;
use App\Domains\Auth\Requests\UpdateUserRequest;
use App\Domains\Auth\Resources\UserResource;
use App\Domains\Auth\Services\UserService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User Management
 *
 * APIs for managing users
 */

class UserController
{
    use ApiResponse;

    public function __construct(
        protected UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            $this->userService->list((int) $request->per_page)
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return $this->success(UserResource::make($user), 'User created successfully.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success(UserResource::make($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update($user, $request->validated());

        return $this->success(UserResource::make($user), 'User updated successfully.');
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return $this->success(message: 'User deleted successfully.');
    }
}
