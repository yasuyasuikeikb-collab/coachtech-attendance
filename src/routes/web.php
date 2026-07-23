<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CorrectionRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/admin/login', [AdminController::class, 'showLoginForm']);
Route::post('/admin/login', [AdminController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'stamp']);
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/break-start', [AttendanceController::class, 'startBreak']);
    Route::post('/attendance/break-end', [AttendanceController::class, 'endBreak']);
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);

    Route::get('/attendance/list', [AttendanceController::class, 'index']);

    Route::get('/attendance/{attendanceRecord}', [AttendanceController::class, 'show'])
        ->whereNumber('attendanceRecord');

    Route::post('/attendance/{attendanceRecord}/correction', [AttendanceController::class, 'requestCorrection'])
        ->whereNumber('attendanceRecord');

    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'index']);

    Route::get('/stamp_correction_request/approve/{correctionRequest}', [CorrectionRequestController::class, 'showApproval'])
        ->whereNumber('correctionRequest');

    Route::post('/stamp_correction_request/approve/{correctionRequest}', [CorrectionRequestController::class, 'approve'])
        ->whereNumber('correctionRequest');

    Route::get('/admin/attendance/list', [AdminController::class, 'attendanceList']);

    Route::get('/admin/attendance/staff/{staffUser}', [AdminController::class, 'staffAttendanceList'])
        ->whereNumber('staffUser');

    Route::get('/admin/attendance/{attendanceRecord}', [AdminController::class, 'attendanceDetail'])
        ->whereNumber('attendanceRecord');

    Route::post('/admin/attendance/{attendanceRecord}/update', [AdminController::class, 'updateAttendance'])
        ->whereNumber('attendanceRecord');

    Route::get('/admin/staff/list', [AdminController::class, 'staffList']);
});