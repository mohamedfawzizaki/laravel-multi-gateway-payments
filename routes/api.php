<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentCallbackController;
use Illuminate\Support\Facades\Validator;

Route::post('sign-up', [AuthController::class, 'signUp']);
Route::post('login', [AuthController::class, 'login']);
Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');



Route::post('/payment/process', [PaymentController::class, 'paymentProcess'])->middleware('auth:sanctum');

# make check HMAC middleware
Route::post('/payment/webhook', [PaymentCallbackController::class, 'webhook']);
# (redirection url) This is where the payment gateway will redirect the customer after payment (or where it sends the payment result).
Route::get('/payment/callback', [PaymentCallbackController::class, 'callback']);
Route::get('/payment/invoice', [PaymentController::class, 'invoice']);

Route::get('test', function (Request $request) {
    $validator = Validator::make(['url' => $request->input('url')], [
        'url' => ['required', 'url'],
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()]);
    }
    return response()->json(['url' => $request->input('url')]);
});