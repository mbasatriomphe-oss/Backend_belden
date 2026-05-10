<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UniteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==================== ROUTES PUBLIQUES (sans authentification) ====================

// Inscription et connexion
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Route publique pour créer le premier admin (à désactiver après utilisation)
Route::post('/register-admin', [AuthController::class, 'registerAdmin']);

// ==================== ROUTES PROTÉGÉES PAR AUTHENTIFICATION ====================

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Déconnexion (nécessite d'être connecté)
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Récupérer l'utilisateur connecté
    Route::get('/user', function(Request $request) {
        return $request->user();
    });
    
    // Routes unités (lecture pour tous les utilisateurs authentifiés)
    Route::prefix('unites')->group(function () {
        Route::get('/', [UniteController::class, 'index'])->name('unites.index');
        Route::get('/all', [UniteController::class, 'all'])->name('unites.all');
        Route::get('/{id}', [UniteController::class, 'show'])->name('unites.show');
    });
    
    // ==================== ROUTES ADMIN UNIQUEMENT ====================
    
    Route::middleware(['admin'])->group(function () {
        // Gestion complète des utilisateurs (CRUD)
        Route::apiResource('users', UserController::class);
        
        // Routes d'écriture pour les unités (CRUD)
        Route::prefix('unites')->group(function () {
            Route::post('/', [UniteController::class, 'store'])->name('unites.store');
            Route::put('/{id}', [UniteController::class, 'update'])->name('unites.update');
            Route::delete('/{id}', [UniteController::class, 'destroy'])->name('unites.destroy');
        });
    });
});