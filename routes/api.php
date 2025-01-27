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
use App\Http\Controllers\Rating\RatingController;
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
    // Route::put('/worker-profile', [AuthController::class, 'WorkerProfileUpdate'])->name('worker-profile.update');
    Route::post('/worker-profile', [AuthController::class, 'WorkerProfileUpdate'])->name('worker-profile.update');

    Route::post('/client-profile', [AuthController::class, 'ClientProfileUpdate'])->name('client-profile.update');

    Route::get('/get-worker-profile', [AuthController::class, 'GetWorkerProfile']);



    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients/store', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show']);

    Route::get('/services', [ServiceController::class, 'index']);
    // Route to accept a service
    Route::post('/services/{service}/accept', [ServiceController::class, 'acceptService']);

    // Route to cancel a service
    Route::post('/services/{service}/cancel', [ServiceController::class, 'cancelService']);

    // Route to complete a service
    Route::post('/services/{service}/complete', [ServiceController::class, 'completeService']);

    Route::get('services/pending' , [ServiceController::class, 'pendingServices']);
    Route::get('services/completed' , [ServiceController::class, 'completedServices']);
    Route::get('services/processing' , [ServiceController::class, 'processingServices']);

    Route::get('services/pending/client' , [ServiceController::class, 'clientPendingServices']);
    Route::get('services/processing/client' , [ServiceController::class, 'clientProcessingServices']);
    Route::get('services/completed/client' , [ServiceController::class, 'clientCompletedServices']);


    Route::post('/workers/store', [WorkerController::class, 'store']);

    Route::post('/ratings', [RatingController::class, 'store'])->name('ratings.store');
    Route::get('ratings/worker/{worker_id}/average', [RatingController::class, 'getAverageRating']);


    Route::get('/payments', [PaymentController::class, 'index']);

    // Route::post('/payment-intent/{client}', [PaymentController::class, 'createPaymentIntent']);

    // Route::post('/confirm-payment/{client}', [PaymentController::class, 'confirmPaymentIntent']);

    // Route::post('/release-payment/{client}', [PaymentController::class, 'releasePayment']);

    Route::post('/refund-payment/{workers}', [PaymentController::class, 'refundPayment']);
});

Route::get('/test-log/{client}', [TestController::class, 'testLog']);
