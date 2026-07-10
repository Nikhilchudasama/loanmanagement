<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function list(int $perPage = 15): array
    {
        $user = auth()->user();
        $query = User::query();

        if (! $user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return UserResource::collection(
            $query->paginate($perPage)
        )->response()->getData(true);
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        $currentUser = auth()->user();
        if (! $currentUser->isSuperAdmin()) {
            $data['tenant_id'] = $currentUser->tenant_id;
        }

        $user = User::create($data);
        $user->assignRole($data['role'] ?? 'borrower');

        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
