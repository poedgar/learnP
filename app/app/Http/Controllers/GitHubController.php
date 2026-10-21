<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Continue with GitHub". The web app finishes with a session, like a normal login. The
 * mobile app opens this flow in an in-app browser and gets back a short-lived one-time code
 * on its own URL scheme, which it exchanges for an API token, so no token is ever in a URL.
 */
class GitHubController extends Controller
{
    private const CODE_TTL_SECONDS = 60;

    public static function enabled(): bool
    {
        return filled(config('services.github.client_id')) && filled(config('services.github.client_secret'));
    }

    public function redirect(Request $request): SymfonyRedirect
    {
        abort_unless(self::enabled(), 404);

        $returnTo = $request->query('return_to');
        if (is_string($returnTo) && $this->isAllowedAppUrl($returnTo)) {
            $request->session()->put('github.return_to', $returnTo);
        } else {
            $request->session()->forget('github.return_to');
        }

        return Socialite::driver('github')->scopes(['user:email'])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        $returnTo = $request->session()->pull('github.return_to');

        try {
            $github = Socialite::driver('github')->user();
        } catch (Throwable) {
            return $this->fail($returnTo, 'github-failed');
        }

        $email = strtolower((string) $github->getEmail());
        if ($email === '') {
            return $this->fail($returnTo, 'github-email');
        }

        $user = User::where('github_id', (string) $github->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user !== null && $user->github_id !== null && $user->github_id !== (string) $github->getId()) {
            // That email's account is already linked to a different GitHub account.
            return $this->fail($returnTo, 'github-linked');
        }

        if ($user === null) {
            $user = new User(['name' => $github->getName() ?: $github->getNickname() ?: 'GitHub user', 'email' => $email]);
            // GitHub only shares addresses its owner has confirmed.
            $user->email_verified_at = now();
        }
        $user->github_id = (string) $github->getId();
        $user->save();

        if ($returnTo !== null) {
            $code = Str::random(48);
            Cache::put("github-login:$code", $user->id, self::CODE_TTL_SECONDS);

            return redirect()->away($returnTo.(str_contains($returnTo, '?') ? '&' : '?').'code='.$code);
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return redirect('/');
    }

    /** The mobile app trades its one-time code for an API token. */
    public function exchange(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string'], 'device_name' => ['required', 'string', 'max:255']]);

        $userId = Cache::pull('github-login:'.$request->input('code'));
        $user = $userId === null ? null : User::find($userId);
        if ($user === null) {
            throw ValidationException::withMessages(['code' => 'That sign-in link has expired. Try again.']);
        }

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken($request->input('device_name'))->plainTextToken,
        ]);
    }

    /**
     * Where the mobile app may receive its code: its own scheme, and in local development
     * also Expo Go's exp:// URLs. Anything else could hand the code to someone else's app.
     */
    private function isAllowedAppUrl(string $url): bool
    {
        $prefixes = ['learnp://'];
        if (app()->environment('local')) {
            $prefixes[] = 'exp://';
        }

        return Str::startsWith($url, $prefixes);
    }

    private function fail(?string $returnTo, string $error): RedirectResponse
    {
        return $returnTo !== null
            ? redirect()->away($returnTo.(str_contains($returnTo, '?') ? '&' : '?').'error='.$error)
            : redirect('/sign-in?error='.$error);
    }
}
