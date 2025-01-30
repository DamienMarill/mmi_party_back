<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\MMIIPartsController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureEmailIsVerifiedApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('/', [UserController::class, 'getMe'])->middleware('auth:api');

Route::group(['middleware' => ['auth:api', EnsureEmailIsVerifiedApi::class]], function () {
    Route::group(['prefix' => '/me'], function (){
        Route::get('/loot', [UserController::class, 'getLoot']);
        Route::get('/loot/availability', [UserController::class, 'checkAvailability']);
//    Route::put('/', [AuthController::class, 'update']);
//    Route::put('/password', [AuthController::class, 'updatePassword']);
//    Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::group(['prefix' => '/mmii'], function (){
        Route::group(['prefix' => '/parts'], function () {
            Route::get('/', [MMIIPartsController::class, 'index']);
            Route::get('/backgrounds', [MMIIPartsController::class, 'indexBackgrounds']);
        });
    });

    Route::group(['prefix' => '/collection'], function (){
        Route::get('/', [CollectionController::class, 'index']);
        Route::get('/{cardVersion}', [CollectionController::class, 'show']);
    });
});

Route::get('assets/{path}', function($path) {
    // Vérifie si le fichier existe
    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    // Récupère le fichier
    $file = Storage::disk('public')->get($path);

    // Détermine le type MIME
    $mimeType = Storage::disk('public')->mimeType($path);

    // Renvoie le fichier avec les bons headers
    return response($file)
        ->header('Content-Type', $mimeType)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Cache-Control', 'public, max-age=31536000');
})->where('path', '.*'); // Permet les sous-dossiers
