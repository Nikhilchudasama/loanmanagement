<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Notifications\ResetPasswordNotification;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Account is inactive.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user->load('tenant'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function me(User $user): User
    {
        return $user->load('tenant');
    }

    public function sendResetLink(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();

        PasswordReset::where('email', $email)->delete();

        $token = Str::random(60);

        PasswordReset::create([
            'email' => $email,
            'token' => Hash::make($token),
            'tenant_id' => $user->tenant_id,
            'created_at' => now(),
        ]);

        $user->notify(new ResetPasswordNotification($token, $email));
    }

    public function resetPassword(string $email, string $token, string $password): void
    {
        $reset = PasswordReset::where('email', $email)->first();

        if (! $reset || ! Hash::check($token, $reset->token)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid or expired password reset token.'],
            ]);
        }

        if (\Illuminate\Support\Carbon::parse($reset->created_at)->diffInMinutes(now()) > 60) {
            $reset->delete();
            throw ValidationException::withMessages([
                'email' => ['Password reset token has expired.'],
            ]);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->update(['password' => $password]);

        $reset->delete();
    }
}
