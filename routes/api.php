<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Password Reset Routes
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');


Route::group([
    "middleware" => "auth:api"
], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/clients/store', [ClientController::class, 'store']);
});
