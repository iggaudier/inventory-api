<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function store(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($credentials);

        return response()->json(['message' => 'If an account exists for this email, a password reset link has been sent.']);
    }

    public function update(Request $request)
    {
        $credentials = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $status = Password::reset($credentials, function (User $user, string $password) {
            if (Hash::check($password, $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Your new password must be different from your current password.',
                ]);
            }

            DB::transaction(function () use ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();
                $user->tokens()->delete();
            });
            event(new PasswordReset($user));
        });

        if ($status !== Password::PasswordReset) {
            return response()->json(['message' => 'This password reset link is invalid or has expired. Request a new link.'], 422);
        }

        return response()->json(['message' => 'Password reset successfully. Sign in with your new password.']);
    }
}
