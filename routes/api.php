<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\WorkerSearchController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Password Reset Routes
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::post('contacts', [ContactController::class, 'store']);
// Route::get('/search-workers', [WorkerSearchController::class, 'search'])->name('search.workers');

Route::post('/search-workers', [WorkerSearchController::class, 'search']);


Route::group([
    "middleware" => "auth:api"
], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/clients/store', [ClientController::class, 'store']);
    Route::post('/workers/store', [WorkerController::class, 'store']);
});
