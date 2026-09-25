<?php

use App\Http\Controllers\RunController;
use App\Http\Middleware\VerifyRunnerSignature;
use Illuminate\Support\Facades\Route;

Route::post('run', RunController::class)->middleware(VerifyRunnerSignature::class)->name('run');
