<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'stamp']);
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/break-start', [AttendanceController::class, 'startBreak']);
    Route::post('/attendance/break-end', [AttendanceController::class, 'endBreak']);
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
    Route::get('/attendance/list', [AttendanceController::class, 'index']);
});