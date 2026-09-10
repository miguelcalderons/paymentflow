<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentWebhookController;

Route::post(
    '/organizations/{organization}/customers/{customer}/payments',
    [PaymentController::class, 'store']
);



Route::post('/webhooks/mock', [PaymentWebhookController::class, 'handle']);
