<?php

use App\Mail\FeedbackMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function feedbackUser(string $role): User
{
    Role::findOrCreate($role, 'web');
    $user = User::factory()->create(['must_change_password' => false]);
    $user->assignRole($role);

    return $user;
}

test('any signed-in user can send feedback', function () {
    Mail::fake();
    config(['mail.feedback_recipient' => 'support@example.com']);
    $user = feedbackUser('group-member');
    Sanctum::actingAs($user);

    $this->postJson('/api/feedback', [
        'type' => 'bug',
        'message' => 'The product filter does not update after I save a change.',
    ])->assertOk()->assertJsonPath('success', true);

    Mail::assertSent(FeedbackMail::class, function (FeedbackMail $mail) use ($user) {
        return $mail->senderEmail === $user->email
            && $mail->senderName === $user->name
            && $mail->feedbackType === 'bug'
            && $mail->hasTo('support@example.com');
    });
});

test('feedback rejects unknown types and empty messages', function () {
    Sanctum::actingAs(feedbackUser('group-admin'));

    $this->postJson('/api/feedback', [
        'type' => 'other',
        'message' => '',
    ])->assertUnprocessable()->assertJsonValidationErrors(['type', 'message']);
});
