<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\GameData\ItemController;
use App\Http\Controllers\Operations\ItemGrantController;
use App\Http\Controllers\Players\LoginLogController;
use App\Http\Controllers\Players\PlayerAccountController;
use App\Http\Controllers\Players\PlayerCharacterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/auth')->group(function () {
    Route::post('/login', [SessionController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [SessionController::class, 'show']);
        Route::post('/logout', [SessionController::class, 'destroy']);
    });
});

Route::prefix('api/players')->middleware(['auth:sanctum', 'permission:players.view'])->group(function () {
    Route::get('/accounts', [PlayerAccountController::class, 'index']);
    Route::post('/accounts', [PlayerAccountController::class, 'store'])->middleware('permission:players.edit');
    Route::get('/characters', [PlayerCharacterController::class, 'index']);
    Route::get('/login-logs', [LoginLogController::class, 'index']);
    Route::patch('/accounts/{accountId}', [PlayerAccountController::class, 'update'])->middleware('permission:players.edit');
    Route::post('/accounts/{accountId}/password', [PlayerAccountController::class, 'password'])->middleware('permission:players.edit');
    Route::post('/accounts/batch', [PlayerAccountController::class, 'batch'])->middleware('permission:players.edit');
    Route::delete('/accounts/{accountId}', [PlayerAccountController::class, 'destroy'])->middleware('permission:players.edit');
});

Route::prefix('api/game-data')->middleware(['auth:sanctum', 'permission:game-data.view'])->group(function () {
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/versions', [ItemController::class, 'versions']);
    Route::get('/items/{itemId}', [ItemController::class, 'show'])->whereNumber('itemId');
    Route::get('/items/{itemId}/icon', [ItemController::class, 'icon'])->whereNumber('itemId');
    Route::get('/items/{itemId}/illustration', [ItemController::class, 'illustration'])->whereNumber('itemId');
});

Route::prefix('api/operations')->middleware(['auth:sanctum', 'permission:operations.item-grant'])->group(function () {
    Route::post('/item-grants/mail', [ItemGrantController::class, 'store']);
});
