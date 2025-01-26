<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::group(['middleware' => 'auth:api', 'prefix' => '/me'], function () {
    Route::get('/', [UserController::class, 'getMe']);
//    Route::put('/', [AuthController::class, 'update']);
//    Route::put('/password', [AuthController::class, 'updatePassword']);
//    Route::post('/logout', [AuthController::class, 'logout']);
});
