<?php

use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('a signed-in user can update only their own name', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'must_change_password' => false,
    ]);
    Sanctum::actingAs($user);

    $this->patchJson('/api/account', [
        'name' => 'Updated Name',
        'email' => 'ignored@example.com',
    ])->assertOk()
        ->assertJsonPath('name', 'Updated Name')
        ->assertJsonPath('email', $user->email);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => $user->email,
    ]);
});

test('account name requires a value', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    Sanctum::actingAs($user);

    $this->patchJson('/api/account', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('a group admin can rename only their organization', function () {
    Role::findOrCreate('group-admin', 'web');
    $organization = Organization::create(['name' => 'Original Studio']);
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'must_change_password' => false,
    ]);
    $user->assignRole('group-admin');
    Sanctum::actingAs($user);

    $this->patchJson('/api/organizations/name', ['name' => 'Renamed Studio'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed Studio');

    $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'name' => 'Renamed Studio']);
});

test('dashboard includes organization only for non-super-admin users', function () {
    Role::findOrCreate('group-member', 'web');
    Role::findOrCreate('super-admin', 'web');
    $organization = Organization::create(['name' => 'Member Studio']);
    $member = User::factory()->create(['organization_id' => $organization->id, 'must_change_password' => false]);
    $member->assignRole('group-member');

    Sanctum::actingAs($member);
    $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('data.organization.name', 'Member Studio');

    $superAdmin = User::factory()->create(['must_change_password' => false]);
    $superAdmin->assignRole('super-admin');
    Sanctum::actingAs($superAdmin);
    $this->getJson('/api/dashboard')->assertOk()->assertJsonMissingPath('data.organization');
});
