<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EquipeController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Employe + Admin
    Route::get('/mon-profil', [UserController::class, 'monProfil']);
    Route::get('/equipes',    [EquipeController::class, 'index']);

    // Admin only
    Route::middleware('admin')->group(function () {
        Route::apiResource('users',   UserController::class);
        Route::apiResource('equipes', EquipeController::class)->except(['index']);
    });
});