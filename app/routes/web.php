<?php

use App\Http\Controllers\EmailVerificationController;
use Illuminate\Support\Facades\Route;

// The link in "confirm your email" messages: signed, so it works without being signed in.
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// The Vue single-page app handles every page route; data comes from routes/api.php.
Route::view('/{any?}', 'app')->where('any', '^(?!api/|up$).*')->name('app');
