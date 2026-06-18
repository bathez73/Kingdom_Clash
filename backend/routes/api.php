<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\BattleController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\ChestController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\KingdomController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SoldierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes d'authentification (sans auth:sanctum)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Routes protégées par auth:sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::put('/auth/user', [AuthController::class, 'update']);

    // Cards
    Route::get('/cards', [CardController::class, 'index']);
    Route::post('/cards/deck', [CardController::class, 'updateDeck']);
    Route::post('/cards/{card}/upgrade', [CardController::class, 'upgradeCard']);
    
    // Chests
    Route::get('/chests', [ChestController::class, 'index']);
    Route::post('/chests/{chest}/start-unlock', [ChestController::class, 'startUnlock']);
    Route::post('/chests/{chest}/open', [ChestController::class, 'open']);
    
    // Battles
    Route::post('/battles/start', [BattleController::class, 'start']);
    Route::post('/battles/finish', [BattleController::class, 'finish']);

    // Royaume
    Route::get('/kingdom', [KingdomController::class, 'show']);
    Route::get('/kingdom/ranking', [KingdomController::class, 'index']);
    Route::get('/kingdom/{id}', [KingdomController::class, 'getById']);
    Route::post('/kingdom/exchange-resources', [KingdomController::class, 'exchangeResources']);
    Route::post('/kingdom/daily-chest', [KingdomController::class, 'claimDailyChest']);

    // Administration - Corbeille (Soft Delete)
    Route::delete('/kingdom/{id}/soft-delete', [KingdomController::class, 'softDelete'])->middleware('role:admin');
    Route::post('/kingdom/{id}/restore', [KingdomController::class, 'restore'])->middleware('role:admin');
    Route::delete('/kingdom/{id}/force-delete', [KingdomController::class, 'forceDelete'])->middleware('role:admin');

    // Bâtiments
    Route::get('/buildings', [BuildingController::class, 'index']);
    Route::post('/buildings/{buildingId}/upgrade', [BuildingController::class, 'upgrade']);

    // Administration - Corbeille Bâtiments
    Route::delete('/buildings/{buildingId}/soft-delete', [BuildingController::class, 'destroy'])->middleware('role:admin');
    Route::post('/buildings/{buildingId}/restore', [BuildingController::class, 'restore'])->middleware('role:admin');
    Route::delete('/buildings/{buildingId}/force-delete', [BuildingController::class, 'forceDelete'])->middleware('role:admin');

    // Soldats
    Route::get('/soldiers', [SoldierController::class, 'index']);
    Route::post('/soldiers/train', [SoldierController::class, 'train']);

    // Batailles
    Route::post('/battles/attack/{opponentId}', [BattleController::class, 'attack']);
    Route::get('/battles/history', [BattleController::class, 'history']);

    // Chat global
    Route::post('/chat/send-message', [ChatController::class, 'sendMessage']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notificationId}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);

    // Administration
    Route::middleware('role:admin')->prefix('/admin')->group(function () {
        Route::get('/users', [AdminController::class, 'getAllUsers']);
        Route::get('/users/trashed', [AdminController::class, 'getTrashedUsers']);
        Route::post('/users/{userId}/restore', [AdminController::class, 'restoreUser']);
        Route::delete('/users/{userId}/force-delete', [AdminController::class, 'forceDeleteUser']);
        Route::post('/users/{userId}/ban', [AdminController::class, 'banUser']);
        Route::post('/users/{userId}/unban', [AdminController::class, 'unbanUser']);

        Route::get('/kingdoms', [AdminController::class, 'getAllKingdoms']);
        Route::get('/kingdoms/trashed', [AdminController::class, 'getTrashedKingdoms']);
        Route::post('/kingdoms/{kingdomId}/restore', [AdminController::class, 'restoreKingdom']);
        Route::delete('/kingdoms/{kingdomId}/force-delete', [AdminController::class, 'forceDeleteKingdom']);
    });

    // Route par défaut pour obtenir l'utilisateur actuel
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
