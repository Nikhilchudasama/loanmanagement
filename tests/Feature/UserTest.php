<?php

use App\Domains\Auth\Models\User;
use App\Domains\Tenant\Models\Tenant;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => \Database\Seeders\RoleAndPermissionSeeder::class]);

    $this->superAdmin = User::create([
        'name' => 'Super Admin',
        'email' => 'super@test.com',
        'password' => bcrypt('password'),
        'status' => 'active',
    ]);
    $this->superAdmin->assignRole('super-admin');

    $this->tenant = Tenant::create([
        'company_name' => 'Test Corp',
        'timezone' => 'UTC',
        'default_currency' => 'USD',
        'status' => 'active',
    ]);
});

it('lists users as super admin', function (): void {
    $this->actingAs($this->superAdmin);

    $response = $this->getJson('/api/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['data', 'meta', 'links'],
        ]);
});

it('creates a user as super admin', function (): void {
    $this->actingAs($this->superAdmin);

    $response = $this->postJson('/api/users', [
        'tenant_id' => $this->tenant->id,
        'name' => 'New User',
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'role' => 'loan-officer',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => ['name' => 'New User', 'email' => 'newuser@test.com'],
        ]);
});

it('requires required fields for user creation', function (): void {
    $this->actingAs($this->superAdmin);

    $response = $this->postJson('/api/users', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tenant_id', 'name', 'email', 'password', 'role']);
});

it('shows a user', function (): void {
    $this->actingAs($this->superAdmin);

    $user = $this->postJson('/api/users', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Show User',
        'email' => 'show@test.com',
        'password' => 'password123',
        'role' => 'borrower',
    ])->json('data');

    $response = $this->getJson("/api/users/{$user['id']}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['name' => 'Show User'],
        ]);
});

it('updates a user', function (): void {
    $this->actingAs($this->superAdmin);

    $user = $this->postJson('/api/users', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Update User',
        'email' => 'update@test.com',
        'password' => 'password123',
        'role' => 'borrower',
    ])->json('data');

    $response = $this->putJson("/api/users/{$user['id']}", [
        'name' => 'Updated Name',
        'role' => 'loan-officer',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['name' => 'Updated Name'],
        ]);
});

it('deletes a user', function (): void {
    $this->actingAs($this->superAdmin);

    $user = $this->postJson('/api/users', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Delete User',
        'email' => 'delete@test.com',
        'password' => 'password123',
        'role' => 'borrower',
    ])->json('data');

    $response = $this->deleteJson("/api/users/{$user['id']}");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

it('auto-fills tenant_id for tenant admin creating users', function (): void {
    $tenantAdmin = createTenantAndUser('admin', 'tenantadmin@test.com');
    $this->actingAs($tenantAdmin);

    $response = $this->postJson('/api/users', [
        'name' => 'Tenant User',
        'email' => 'tenantuser@test.com',
        'password' => 'password123',
        'role' => 'borrower',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.tenant_id'))->toEqual($tenantAdmin->tenant_id);
});
