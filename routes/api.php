<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EquipeController;
use App\Http\Controllers\TacheController;
use App\Http\Controllers\PlanningController;
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
     Route::get('/taches',  [TacheController::class,  'index']);
     Route::get('/mon-planning', [PlanningController::class, 'monPlanning']);

    // Admin only
    Route::middleware('admin')->group(function () {
        Route::apiResource('users',   UserController::class);
        Route::apiResource('equipes', EquipeController::class)->except(['index']);
        Route::apiResource('taches',  TacheController::class)->except(['index']);
        Route::apiResource('plannings', PlanningController::class);
        Route::post('plannings/{id}/taches',              [PlanningController::class, 'addTache']);
        Route::delete('plannings/{id}/taches/{tache_id}', [PlanningController::class, 'removeTache']);

    });
});