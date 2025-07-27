<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(["prefix" => "auth"], function () {
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
    Route::get("google_login", [AuthenticatedSessionController::class, 'googleLogin'])->name('googleLogin');
    Route::get('login_complete', [AuthenticatedSessionController::class, 'googleCallback'])
        ->name('login_complete');
});
Route::group(['prefix' => 'product'], function () {
    Route::post('/', [ProductController::class, 'getProducts'])
        ->name('getProducts');
});
Route::group(['prefix' => 'category'], function () {
    Route::get('/', [ProductCategoryController::class, 'getCategories']);
});
Route::group(["prefix" => "order"], function () {
    Route::post('/', [OrderController::class, 'getOlderOrders']);
    Route::post('/payment-done', [OrderController::class, 'paymentDoneOrder']);
});
