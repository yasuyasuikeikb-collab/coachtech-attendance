<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/attendance-records', [AttendanceRecordController::class, 'index']);

    Route::get('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'show'])
        ->whereNumber('attendanceRecord');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/attendance-records', [AttendanceRecordController::class, 'store']);

        Route::put('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update'])
            ->whereNumber('attendanceRecord');

        Route::delete('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'destroy'])
            ->whereNumber('attendanceRecord');
    });
});