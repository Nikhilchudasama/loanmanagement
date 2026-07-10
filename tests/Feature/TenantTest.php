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
});

it('lists tenants as super admin', function (): void {
    $this->actingAs($this->superAdmin);

    $response = $this->getJson('/api/tenants');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data',
        ]);
});

it('creates a tenant as super admin', function (): void {
    $this->actingAs($this->superAdmin);

    $response = $this->postJson('/api/tenants', [
        'company_name' => 'New Corp',
        'timezone' => 'Asia/Kolkata',
        'default_currency' => 'INR',
        'status' => 'active',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => ['company_name' => 'New Corp'],
        ]);
});

it('requires required fields for tenant creation', function (): void {
    $this->actingAs($this->superAdmin);

    $response = $this->postJson('/api/tenants', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['company_name']);
});

it('shows a tenant', function (): void {
    $this->actingAs($this->superAdmin);

    $tenant = $this->postJson('/api/tenants', [
        'company_name' => 'Show Corp',
    ])->json('data');

    $response = $this->getJson("/api/tenants/{$tenant['id']}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['company_name' => 'Show Corp'],
        ]);
});

it('updates a tenant', function (): void {
    $this->actingAs($this->superAdmin);

    $tenant = $this->postJson('/api/tenants', [
        'company_name' => 'Update Corp',
    ])->json('data');

    $response = $this->putJson("/api/tenants/{$tenant['id']}", [
        'company_name' => 'Updated Corp',
        'default_currency' => 'EUR',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['company_name' => 'Updated Corp'],
        ]);
});

it('deletes a tenant', function (): void {
    $this->actingAs($this->superAdmin);

    $tenant = $this->postJson('/api/tenants', [
        'company_name' => 'Delete Corp',
    ])->json('data');

    $response = $this->deleteJson("/api/tenants/{$tenant['id']}");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

it('rejects non-super-admin from tenant endpoints', function (): void {
    $tenantAdmin = createTenantAndUser('admin', 'tenantadmin@test.com');
    $this->actingAs($tenantAdmin);

    $this->getJson('/api/tenants')->assertStatus(403);
    $this->postJson('/api/tenants', ['company_name' => 'X'])->assertStatus(403);
    $this->getJson('/api/tenants/1')->assertStatus(403);
    $this->putJson('/api/tenants/1', [])->assertStatus(403);
    $this->deleteJson('/api/tenants/1')->assertStatus(403);
});
