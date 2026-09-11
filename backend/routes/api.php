<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/organizations/{organization}/customers/{customer}/payments',
    [PaymentController::class, 'store']
)->middleware('organization.api-key');

Route::post(
    '/webhooks/mock',
    [PaymentWebhookController::class, 'handle']
);
