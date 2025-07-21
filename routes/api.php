<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/try', function () {
    return response()->json(['message' => 'This is a test route']);
});
Route::group(["prefix" => "cart"], function () {
    Route::get('/', [CartController::class, 'getCart']);
    Route::post('/update', [CartController::class, 'updateCart']);
    Route::post('/delete', [CartController::class, 'deleteCart']);
    Route::post('/payment-done', [CartController::class, 'paymentDoneCart']);
});
Route::group(["prefix" => "order"], function () {
    Route::get('/', [OrderController::class, 'getOrders']);
});
