<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Confirming an email address. The link in the email is a signed URL, so it works without
 * being signed in, which matters when it's opened on a different device than the app.
 */
class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect('/account?verified=1');
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Your email address is already confirmed.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'We have sent you a new confirmation link.']);
    }
}
