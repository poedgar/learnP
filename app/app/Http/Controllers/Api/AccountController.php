<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use App\Models\ReviewCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/** A learner's own account: everything we hold about them, and deleting it for good. */
class AccountController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'signs_in_with_github' => $user->github_id !== null,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'progress' => $user->lessonProgress()->with('lesson.course')->get()->map(fn (LessonProgress $progress) => [
                'course' => $progress->lesson?->course?->slug,
                'lesson' => $progress->lesson?->slug,
                ...$progress->toClient(),
                'updated_at' => $progress->updated_at?->toIso8601String(),
            ]),
            'review_cards' => $user->reviewCards()->get()->map(fn (ReviewCard $card) => ['item' => $card->item_key, ...$card->toClient()]),
        ], 200, ['Content-Disposition' => 'attachment; filename="learnp-data.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Deletes the account and everything that belongs to it. Accounts with a password must
     * give it; accounts made with GitHub type their email address to confirm instead.
     */
    public function destroy(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasPassword()) {
            $request->validate(['password' => ['required', 'string']]);
            if (! Hash::check($request->input('password'), $user->password)) {
                throw ValidationException::withMessages(['password' => 'That password is not right.']);
            }
        } else {
            $request->validate(['confirmation' => ['required', 'string']]);
            if (strtolower(trim($request->input('confirmation'))) !== strtolower($user->email)) {
                throw ValidationException::withMessages(['confirmation' => 'Type your email address to confirm.']);
            }
        }

        // Sign the browser out first: logging out saves the user (it renews the remember-me
        // token), which after deleting would quietly create the account again.
        if (! $user->currentAccessToken() instanceof PersonalAccessToken && $request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            // Progress and review cards go with the user (foreign keys cascade).
            $user->delete();
        });

        return response()->noContent();
    }
}
