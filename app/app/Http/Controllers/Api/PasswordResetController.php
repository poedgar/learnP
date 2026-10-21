<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * "Forgot your password?" for both apps. The emailed link opens the web app's
 * /reset-password page (see AppServiceProvider), which posts the new password here.
 */
class PasswordResetController extends Controller
{
    /** Always the same answer, so the form can't be used to find out who has an account. */
    public const SENT = 'If that email address has an account, we have sent it a link to reset the password.';

    public function sendLink(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        Password::sendResetLink(['email' => strtolower(trim($request->input('email')))]);

        return response()->json(['message' => self::SENT]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            [...$request->only('token', 'password', 'password_confirmation'), 'email' => strtolower(trim($request->input('email')))],
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                // Whoever knew the old password is signed out everywhere: apps and browsers.
                $user->tokens()->delete();
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => __($status)]);
    }
}
