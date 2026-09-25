<?php

use Illuminate\Support\Facades\Route;

// The Vue single-page app handles every page route; data comes from routes/api.php.
Route::view('/{any?}', 'app')->where('any', '^(?!api/|up$).*')->name('app');
