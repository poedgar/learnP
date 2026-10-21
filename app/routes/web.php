<?php

use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\GitHubController;
use Illuminate\Support\Facades\Route;

// The link in "confirm your email" messages: signed, so it works without being signed in.
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::get('/auth/github', [GitHubController::class, 'redirect'])->name('github.redirect');
Route::get('/auth/github/callback', [GitHubController::class, 'callback'])->name('github.callback');

// The Vue single-page app handles every page route; data comes from routes/api.php.
Route::view('/{any?}', 'app')->where('any', '^(?!api/|up$).*')->name('app');
