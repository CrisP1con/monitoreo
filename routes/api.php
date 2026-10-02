<?php

use App\Http\Controllers\Api\DiagnosticReportController;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Middleware\AuthenticateDiagnosticAgent;
use Illuminate\Support\Facades\Route;

Route::post('/diagnostics/reports', [DiagnosticReportController::class, 'store'])
    ->middleware([AuthenticateDiagnosticAgent::class, 'throttle:60,1']);

Route::post('/diagnostics/telemetry', [TelemetryController::class, 'store'])
    ->middleware([AuthenticateDiagnosticAgent::class, 'throttle:60,1']);
