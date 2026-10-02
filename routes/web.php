<?php

use App\Http\Controllers\ConnectivityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HardwareHistoryController;
use App\Http\Controllers\RecordsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('connectivity', [ConnectivityController::class, 'index'])->name('connectivity.index');
    Route::post('connectivity', [ConnectivityController::class, 'store'])->name('connectivity.store');
    Route::post('connectivity/{agentConnection}/rotate', [ConnectivityController::class, 'rotate'])->name('connectivity.rotate');
    Route::post('connectivity/{agentConnection}/revoke', [ConnectivityController::class, 'revoke'])->name('connectivity.revoke');
    Route::get('records', [RecordsController::class, 'index'])->name('records.index');
    Route::get('records/{agentConnection}', [RecordsController::class, 'show'])->name('records.show');
    Route::get('records/{agentConnection}/hardware', [HardwareHistoryController::class, 'show'])->name('records.hardware');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
