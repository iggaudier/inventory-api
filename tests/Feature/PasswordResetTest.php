<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('forgot password sends a reset email with the frontend link and hides unknown accounts', function () {
    Notification::fake();
    config(['app.frontend_url' => 'https://lily.example']);
    $user = User::factory()->create();
    $response = $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;
        expect($url)->toStartWith('https://lily.example/auth/reset-password?');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        expect($query['email'])->toBe($user->email);
        expect(Password::tokenExists($user, $query['token']))->toBeTrue();

        return true;
    });
    $this->postJson('/api/forgot-password', ['email' => 'missing@example.com'])
        ->assertOk()->assertExactJson($response->json());
});

test('a reset changes the password revokes sessions and consumes the token', function () {
    $user = User::factory()->create(['must_change_password' => true]);
    $user->createToken('existing');
    $token = Password::createToken($user);
    $payload = ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'];
    $this->postJson('/api/reset-password', $payload)->assertOk();
    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->must_change_password)->toBeFalse();
    expect($user->tokens()->count())->toBe(0);
    $this->postJson('/api/reset-password', $payload)->assertUnprocessable();
});

test('invalid expired and weak reset requests preserve the password', function () {
    $user = User::factory()->create();
    $original = $user->password;
    $token = Password::createToken($user);
    $payload = ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'];
    $this->postJson('/api/reset-password', [...$payload, 'token' => 'invalid'])->assertUnprocessable();
    $this->postJson('/api/reset-password', [...$payload, 'password_confirmation' => 'different'])->assertUnprocessable();
    $this->postJson('/api/reset-password', [...$payload, 'password' => 'weak', 'password_confirmation' => 'weak'])->assertUnprocessable();
    $this->travel(61)->minutes();
    $this->postJson('/api/reset-password', $payload)->assertUnprocessable();
    expect($user->fresh()->password)->toBe($original);
});

test('reset rejects the current password without consuming the reset link or revoking sessions', function () {
    $user = User::factory()->create(['password' => Hash::make('ExistingPassword123!')]);
    $original = $user->password;
    $user->createToken('existing');
    $token = Password::createToken($user);
    $payload = ['email' => $user->email, 'token' => $token, 'password' => 'ExistingPassword123!', 'password_confirmation' => 'ExistingPassword123!'];

    $this->postJson('/api/reset-password', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
    expect($user->fresh()->password)->toBe($original);
    expect($user->tokens()->count())->toBe(1);
    expect(Password::tokenExists($user, $token))->toBeTrue();

    $this->postJson('/api/reset-password', [...$payload, 'password' => 'DifferentPassword123!', 'password_confirmation' => 'DifferentPassword123!'])
        ->assertOk();
});
