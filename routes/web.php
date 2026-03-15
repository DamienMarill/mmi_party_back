<?php

use App\Http\Controllers\MoodleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin Moodle OAuth: finalize session login (needs web middleware for cookies)
Route::get('/admin/auth/moodle/finalize', [MoodleAuthController::class, 'adminFinalize'])
    ->name('admin.moodle.finalize');
