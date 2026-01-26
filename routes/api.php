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
    Route::put('/register/{registrationId}', [AuthController::class, 'finalizeRegistration']);
});
Route::post('/me/verify_code', [AuthController::class, 'verifyCode'])->middleware('throttle:6,1');

Route::group(['middleware' => 'auth:api', 'prefix' => '/me'], function () {
    Route::get('/', [UserController::class, 'getMe'])->middleware('auth:api');
});

Route::group(['prefix' => '/mmii'], function () {
    Route::group(['prefix' => '/parts'], function () {
        Route::get('/', [MMIIPartsController::class, 'index']);
        Route::put('/', [MMIIPartsController::class, 'update']);
        Route::group(['prefix' => '/backgrounds'], function () {
            Route::get('/', [MMIIPartsController::class, 'indexBackgrounds']);
            Route::put('/', [MMIIPartsController::class, 'updateBackgrounds']);
        });

    });
});

Route::group(['middleware' => ['auth:api', EnsureEmailIsVerifiedApi::class]], function () {
    Route::group(['prefix' => '/me'], function () {
        Route::get('/loot', [UserController::class, 'getLoot']);
        Route::get('/loot/availability', [UserController::class, 'checkAvailability']);
        //    Route::put('/', [AuthController::class, 'update']);
//    Route::put('/password', [AuthController::class, 'updatePassword']);
//    Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::group(['prefix' => '/collection'], function () {
        Route::get('/', [CollectionController::class, 'index']);
        Route::get('/{cardVersion}', [CollectionController::class, 'show']);
    });
});

Route::group(['prefix' => 'push', 'middleware' => 'auth:api'], function () {
    Route::get('/vapid-key', [\App\Http\Controllers\PushSubscriptionController::class, 'vapidPublicKey']);
    Route::get('/status', [\App\Http\Controllers\PushSubscriptionController::class, 'status']);
    Route::post('/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'subscribe']);
    Route::post('/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe']);
});

Route::get('assets/{path}', function ($path) {
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
