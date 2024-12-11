<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'auth'
], function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::apiResource('accounts', ChartOfAccountController::class);
    Route::apiResource('category-accounts', AccountController::class);
    Route::delete('delete-selected-account', [ChartOfAccountController::class, 'deleteAll']);

    Route::apiResource('products', ProductController::class);

    Route::post('orders', [OrderController::class, 'store']);
});
