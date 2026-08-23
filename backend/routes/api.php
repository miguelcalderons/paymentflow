<?php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/organizations/{organization}/customers/{customer}/payments',
    [PaymentController::class, 'store']
);
