<?php

use App\Http\Controllers\Api\ChallengeCheckController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\QuizAnswerController;
use App\Http\Controllers\Api\ReviewItemsController;
use App\Http\Controllers\Api\SandboxController;
use Illuminate\Support\Facades\Route;

Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
Route::get('courses/{course:slug}/lessons/{lesson:slug}', [LessonController::class, 'show'])
    ->scopeBindings()
    ->name('lessons.show');

Route::post('quiz-questions/{question}/answer', QuizAnswerController::class)->name('quiz-questions.answer');
Route::post('review-items', ReviewItemsController::class)->name('review-items');

Route::middleware('throttle:sandbox')->group(function () {
    Route::post('lessons/{lesson}/run', SandboxController::class)->name('lessons.run');
    Route::post('challenges/{challenge}/check', ChallengeCheckController::class)->name('challenges.check');
});
