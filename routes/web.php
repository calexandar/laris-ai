<?php

use App\Http\Controllers\VoiceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Voice API routes
Route::prefix('api/voice')->group(function () {
    Route::post('/transcribe', [VoiceController::class, 'transcribe']);
    Route::post('/speak', [VoiceController::class, 'speak']);
    Route::post('/ask', [VoiceController::class, 'ask']);
    Route::post('/interact', [VoiceController::class, 'interact']);
});
