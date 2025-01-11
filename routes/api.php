<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\WorkerSearchController;
use App\Http\Controllers\Service\ServiceController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Password Reset Routes
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::post('contacts', [ContactController::class, 'store']);
// Route::get('/search-workers', [WorkerSearchController::class, 'search'])->name('search.workers');

Route::get('/workers', [WorkerController::class, 'index']);
Route::get('/workers/{worker}', [WorkerController::class, 'show']);
Route::post('/search-workers', [WorkerSearchController::class, 'search']);


Route::group([
    "middleware" => "auth:api"
], function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients/store', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);

    Route::post('/book-service', [ServiceController::class, 'bookService']);

    Route::post('/workers/store', [WorkerController::class, 'store']);

    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payment-intent/{client}', [PaymentController::class, 'createPaymentIntent']);
    Route::post('/confirm-payment/{client}', [PaymentController::class, 'confirmPaymentIntent']);
    Route::post('/release-payment/{client}', [PaymentController::class, 'releasePayment']);
    Route::post('/refund-payment/{workers}', [PaymentController::class, 'refundPayment']);

});

Route::get('/test-log/{client}', [TestController::class, 'testLog']);

