<?php

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Notifications\ResetPasswordNotification;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => \Database\Seeders\RoleAndPermissionSeeder::class]);
    $this->artisan('db:seed', ['--class' => \Database\Seeders\TestDataSeeder::class]);
});

it('allows login with valid credentials', function (): void {
    $response = $this->postJson('/api/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['user', 'token'],
        ]);
});

it('rejects login with invalid credentials', function (): void {
    $response = $this->postJson('/api/login', [
        'email' => 'admin@test.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

it('allows authenticated user to access me endpoint', function (): void {
    $user = User::where('email', 'admin@test.com')->first();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->getJson('/api/me');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['email' => 'admin@test.com'],
        ]);
});

it('rejects unauthenticated user from protected routes', function (): void {
    $response = $this->getJson('/api/me');

    $response->assertStatus(401);
});

it('rate limits login after 5 attempts per minute', function (): void {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->postJson('/api/login', [
        'email' => 'admin@test.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
});

it('allows user to logout', function (): void {
    $user = User::where('email', 'admin@test.com')->first();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/logout');

    $response->assertStatus(200);
});

it('sends password reset link for valid email', function (): void {
    Notification::fake();

    $response = $this->postJson('/api/forgot-password', [
        'email' => 'admin@test.com',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Password reset link sent to your email.',
        ]);

    $user = User::where('email', 'admin@test.com')->first();
    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('rejects forgot-password for non-existent email', function (): void {
    $response = $this->postJson('/api/forgot-password', [
        'email' => 'nonexistent@test.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('resets password with valid token', function (): void {
    $token = Str::random(60);
    PasswordReset::create([
        'email' => 'admin@test.com',
        'token' => Hash::make($token),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/reset-password', [
        'email' => 'admin@test.com',
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Password has been reset successfully.',
        ]);

    $user = User::where('email', 'admin@test.com')->first();
    expect(Hash::check('new-password', $user->password))->toBeTrue();
});

it('rejects reset-password with invalid token', function (): void {
    PasswordReset::create([
        'email' => 'admin@test.com',
        'token' => Hash::make('valid-token'),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/reset-password', [
        'email' => 'admin@test.com',
        'token' => 'invalid-token',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('rejects reset-password with expired token', function (): void {
    $token = Str::random(60);
    PasswordReset::create([
        'email' => 'admin@test.com',
        'token' => Hash::make($token),
        'created_at' => now()->subHours(2),
    ]);

    $response = $this->postJson('/api/reset-password', [
        'email' => 'admin@test.com',
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
