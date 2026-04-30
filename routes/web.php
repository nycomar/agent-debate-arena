<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DebateController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debate', [DebateController::class, 'index']);

// API routes without CSRF (for the SPA)
Route::prefix('api')->group(function () {
    Route::post('/debate/create', [DebateController::class, 'create']);
    Route::get('/debate/{id}', [DebateController::class, 'show']);
    Route::post('/debate/{id}/debate', [DebateController::class, 'debate']);
    Route::get('/debates', [DebateController::class, 'list']);
});
