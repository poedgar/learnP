<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChallengeCheckController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LearningEventController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\QuizAnswerController;
use App\Http\Controllers\Api\ReviewItemsController;
use App\Http\Controllers\Api\SandboxController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TrackController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\GitHubController;
use Illuminate\Support\Facades\Route;

Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
Route::get('courses/{course:slug}/lessons/{lesson:slug}', [LessonController::class, 'show'])
    ->scopeBindings()
    ->name('lessons.show');

Route::post('quiz-questions/{question}/answer', QuizAnswerController::class)->name('quiz-questions.answer');
Route::post('review-items', ReviewItemsController::class)->name('review-items');
Route::get('tracks', [TrackController::class, 'index'])->name('tracks.index');
Route::get('search', SearchController::class)->middleware('throttle:60,1')->name('search');

Route::middleware('throttle:events')->group(function () {
    Route::post('challenges/{challenge}/attempts', [LearningEventController::class, 'check'])->name('challenges.attempts');
    Route::post('challenges/{challenge}/hint-views', [LearningEventController::class, 'hint'])->name('challenges.hint-views');
    Route::get('challenges/{challenge}/solution', [LearningEventController::class, 'solution'])->name('challenges.solution');
});

Route::middleware('throttle:sandbox')->group(function () {
    Route::post('lessons/{lesson}/run', SandboxController::class)->name('lessons.run');
    Route::post('challenges/{challenge}/check', ChallengeCheckController::class)->name('challenges.check');
});

Route::middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.store');
    Route::post('auth/github/exchange', [GitHubController::class, 'exchange'])->name('github.exchange');
});

Route::get('auth/options', [AuthController::class, 'options'])->name('auth.options');

// Open to guests, who get null: the apps ask on every launch, and a 401 there isn't an error.
Route::get('user', [AuthController::class, 'show'])->name('user');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('progress', [ProgressController::class, 'show'])->name('progress.show');
    Route::put('progress', [ProgressController::class, 'update'])->name('progress.update');
    Route::post('progress/merge', [ProgressController::class, 'merge'])->name('progress.merge');

    Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('account/export', [AccountController::class, 'export'])->name('account.export');
    Route::delete('account', [AccountController::class, 'destroy'])->name('account.destroy');
});
