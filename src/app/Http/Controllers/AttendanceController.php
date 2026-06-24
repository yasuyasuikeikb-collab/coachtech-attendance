<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceBreakStartService;
use App\Services\Attendance\AttendanceClockInService;
use App\Services\Attendance\AttendanceStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function stamp(
        Request $request,
        AttendanceStatusService $attendanceStatusService
    ): View {
        $attendanceRecord = AttendanceRecord::where('user_id', $request->user()->id)
            ->whereDate('date', today())
            ->first();

        $attendanceStatus = $attendanceStatusService->getStatus($attendanceRecord);
        $canClockIn = $attendanceStatusService->canClockIn($attendanceRecord);
        $canStartBreak = $attendanceStatusService->canStartBreak($attendanceRecord);

        return view('attendance.stamp', [
            'attendanceStatus' => $attendanceStatus,
            'canClockIn' => $canClockIn,
            'canStartBreak' => $canStartBreak,
        ]);
    }

    public function clockIn(
        Request $request,
        AttendanceClockInService $attendanceClockInService
    ): RedirectResponse {
        $attendanceClockInService->clockIn($request->user());

        return redirect('/attendance');
    }

    public function startBreak(
        Request $request,
        AttendanceBreakStartService $attendanceBreakStartService
    ): RedirectResponse {
        $attendanceBreakStartService->startBreak($request->user());

        return redirect('/attendance');
    }
}