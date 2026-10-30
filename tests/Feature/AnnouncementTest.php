<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function announcementUser(string $role): User
{
    Role::findOrCreate($role, 'web');
    $user = User::factory()->create(['must_change_password' => false]);
    $user->assignRole($role);

    return $user;
}

test('every authenticated user can read announcements', function () {
    $user = announcementUser('group-member');
    $author = announcementUser('super-admin');
    Announcement::create([
        'title' => 'Platform update',
        'body' => 'A new feature is available.',
        'created_by' => $author->id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/announcements')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');
});

test('only super admins can publish announcements', function () {
    Sanctum::actingAs(announcementUser('group-admin'));

    $this->postJson('/api/announcements', [
        'title' => 'Restricted update',
        'body' => 'This must not be published.',
    ])->assertForbidden();
});

test('only super admins can edit or delete announcements', function () {
    $author = announcementUser('super-admin');
    $announcement = Announcement::create([
        'title' => 'Platform update',
        'body' => 'A new feature is available.',
        'created_by' => $author->id,
    ]);

    Sanctum::actingAs(announcementUser('group-admin'));

    $this->putJson("/api/announcements/{$announcement->id}", ['title' => 'Changed', 'body' => 'Nope.'])->assertForbidden();
    $this->deleteJson("/api/announcements/{$announcement->id}")->assertForbidden();
});

test('a super admin can publish a validated image announcement', function () {
    Storage::fake('public');
    Sanctum::actingAs(announcementUser('super-admin'));

    $response = $this->post('/api/announcements', [
        'title' => 'Platform update',
        'body' => 'A new feature is available.',
        'media' => UploadedFile::fake()->image('update.jpg'),
    ]);

    $response->assertCreated()->assertJsonPath('data.title', 'Platform update');
    $announcement = Announcement::firstOrFail();

    expect($announcement->media_path)->not->toBeNull();
    Storage::disk('public')->assertExists($announcement->media_path);
});

test('a super admin can edit and delete an announcement with its media', function () {
    Storage::fake('public');
    Sanctum::actingAs(announcementUser('super-admin'));

    $this->post('/api/announcements', [
        'title' => 'Platform update',
        'body' => 'A new feature is available.',
        'media' => UploadedFile::fake()->image('old.jpg'),
    ])->assertCreated();

    $announcement = Announcement::firstOrFail();
    $oldPath = $announcement->media_path;

    $this->put("/api/announcements/{$announcement->id}", [
        'title' => 'Revised update',
        'body' => 'The details have changed.',
        'media' => UploadedFile::fake()->image('new.jpg'),
    ])->assertOk()->assertJsonPath('data.title', 'Revised update');

    Storage::disk('public')->assertMissing($oldPath);
    $newPath = $announcement->fresh()->media_path;
    Storage::disk('public')->assertExists($newPath);

    $this->deleteJson("/api/announcements/{$announcement->id}")->assertOk();
    $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    Storage::disk('public')->assertMissing($newPath);
});
