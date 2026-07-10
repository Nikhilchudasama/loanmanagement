<?php

use App\Domains\Auth\Models\User;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => \Database\Seeders\RoleAndPermissionSeeder::class]);

    $this->user = createTenantAndUser('admin', 'admin@test.com');
    $this->actingAs($this->user);
});

it('lists borrowers', function (): void {
    $response = $this->getJson('/api/borrowers');

    $response->assertStatus(200);
});

it('creates a borrower', function (): void {
    $response = $this->postJson('/api/borrowers', [
        'full_name' => 'John Doe',
        'email' => 'john@example.com',
        'mobile_number' => '+1234567890',
        'date_of_birth' => '1990-01-15',
        'address' => '123 Main St',
        'national_id' => 'NID123',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => ['full_name' => 'John Doe'],
        ]);
});

it('requires required fields for borrower creation', function (): void {
    $response = $this->postJson('/api/borrowers', []);

    $response->assertStatus(422);
});

it('shows a borrower', function (): void {
    $borrower = $this->postJson('/api/borrowers', [
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'mobile_number' => '+9876543210',
    ])->json('data');

    $response = $this->getJson("/api/borrowers/{$borrower['id']}");

    $response->assertStatus(200);
});

it('updates a borrower', function (): void {
    $borrower = $this->postJson('/api/borrowers', [
        'full_name' => 'Bob',
        'email' => 'bob@example.com',
        'mobile_number' => '+1111111111',
    ])->json('data');

    $response = $this->putJson("/api/borrowers/{$borrower['id']}", [
        'full_name' => 'Bob Updated',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => ['full_name' => 'Bob Updated'],
        ]);
});

it('deletes a borrower', function (): void {
    $borrower = $this->postJson('/api/borrowers', [
        'full_name' => 'Delete Me',
        'email' => 'delete@example.com',
        'mobile_number' => '+1000000000',
    ])->json('data');

    $response = $this->deleteJson("/api/borrowers/{$borrower['id']}");

    $response->assertStatus(200);
});

it('returns empty data structure when no borrowers exist', function (): void {
    $newUser = createTenantAndUser('admin', 'empty@test.com');
    $this->actingAs($newUser);

    $response = $this->getJson('/api/borrowers');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['data', 'meta', 'links'],
        ]);
    expect($response->json('data.data'))->toBe([]);
});
