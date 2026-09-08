<?php

use App\Http\Controllers\AdventureTools\AdventureToolController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'happyro-admin-backend',
    ]);
});

Route::prefix('adventure-tools')->middleware(['game.session', 'throttle:adventure-tools'])->group(function (): void {
    Route::get('/bootstrap', [AdventureToolController::class, 'bootstrap']);
    Route::get('/character', [AdventureToolController::class, 'character']);
    Route::post('/character/commands', [AdventureToolController::class, 'maintain']);
    Route::get('/game-rules', [AdventureToolController::class, 'gameRules']);
    Route::put('/game-rules', [AdventureToolController::class, 'applyGameRules']);
});
