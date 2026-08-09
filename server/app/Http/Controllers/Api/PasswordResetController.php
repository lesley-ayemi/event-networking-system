<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    /**
     * Always responds with the same generic message regardless of whether
     * the email exists, so this endpoint can't be used to enumerate accounts.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If an account exists for that email, a password reset link is on its way.',
        ]);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => $request->validated('password'),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw new ApiException(
                'This password reset link is invalid or has expired.',
                'INVALID_RESET_TOKEN',
                422
            );
        }

        return response()->json(['message' => 'Your password has been reset. You can now log in.']);
    }
}
