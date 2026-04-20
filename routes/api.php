<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EquipeController;
use App\Http\Controllers\TacheController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ReposController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ==================
// PUBLIC
// ==================
Route::post('/login', [AuthController::class, 'login']);

// ==================
// PROTECTED
// ==================
Route::middleware('auth:sanctum')->group(function () {

    //public 
    Route::post('/logout',      [AuthController::class,    'logout']);
    Route::get('/me',           [AuthController::class,    'me']);

    // Employe + Admin
    Route::get('/mon-profil',   [UserController::class,    'monProfil']);
    Route::get('/mon-planning', [PlanningController::class,'monPlanning']);
    Route::get('/equipes',      [EquipeController::class,  'index']);
    Route::get('/taches',       [TacheController::class,   'index']);

    // Employe
    Route::post('/repos',       [ReposController::class,   'store']);
    Route::get('/mes-demandes', [ReposController::class,   'mesDemandes']);

    // ==================
    // ADMIN ONLY
    // ==================
    Route::middleware('admin')->group(function () {

        // Users
        Route::apiResource('users', UserController::class);

        // Equipes
        Route::apiResource('equipes', EquipeController::class)->except(['index']);

        // Taches
        Route::apiResource('taches', TacheController::class)->except(['index']);

        // Plannings — import + template QBEL apiResource
        Route::post('plannings/import',  [PlanningController::class, 'importExcel']);
        Route::get('plannings/template', [PlanningController::class, 'templateExcel']);
        Route::apiResource('plannings',  PlanningController::class);
        Route::post('plannings/{id}/taches',              [PlanningController::class, 'addTache']);
        Route::delete('plannings/{id}/taches/{tache_id}', [PlanningController::class, 'removeTache']);

        // Repos
        Route::get('/repos',                   [ReposController::class,   'index']);
        Route::put('/repos/{id}/valider',      [ReposController::class,   'valider']);
        Route::put('/repos/{id}/refuser',      [ReposController::class,   'refuser']);
        Route::get('/notifications',           [ReposController::class,   'notifications']);
        Route::put('/notifications/{id}/read', [ReposController::class,   'markAsRead']);

        // Dashboard
        Route::get('/dashboard',            [DashboardController::class, 'index']);
        Route::get('/rapport-hebdomadaire', [DashboardController::class, 'rapportHebdomadaire']);
    });
});