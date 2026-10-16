<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Accounts are optional: lessons stay public, and signing in keeps progress in step
 * across devices. The web app signs in with a session cookie (Sanctum's SPA mode);
 * the mobile app sends a device_name and gets an API token instead.
 */
class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']));

        return $this->signIn($request, $user)->setStatusCode(201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', strtolower($request->validated('email')))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return $this->signIn($request, $user);
    }

    public function logout(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }

    /** The signed-in user, or null for a guest. */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        return response()->json(['data' => $user ? new UserResource($user) : null]);
    }

    private function signIn(Request $request, User $user): JsonResponse
    {
        if ($request->filled('device_name')) {
            return response()->json([
                'user' => new UserResource($user),
                'token' => $user->createToken($request->input('device_name'))->plainTextToken,
            ]);
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();

        return response()->json(['user' => new UserResource($user)]);
    }
}
