<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentCallbackController;



Route::post('sign-up', [AuthController::class, 'signUp']);
Route::post('login', [AuthController::class, 'login']);
Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');



Route::post('/payment/process', [PaymentController::class, 'paymentProcess'])->middleware('auth:sanctum');

# make check HMAC middleware
Route::post( '/payment/processed-callback', [PaymentCallbackController::class, 'processedCallback']);
# (redirection url) This is where the payment gateway will redirect the customer after payment (or where it sends the payment result).
Route::get( '/payment/response-callback', [PaymentCallbackController::class, 'responseCallback']);
Route::get('/payment/invoice', [PaymentController::class, 'invoice']);