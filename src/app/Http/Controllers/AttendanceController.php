<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceBreakEndService;
use App\Services\Attendance\AttendanceBreakStartService;
use App\Services\Attendance\AttendanceClockInService;
use App\Services\Attendance\AttendanceClockOutService;
use App\Services\Attendance\AttendanceStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $canEndBreak = $attendanceStatusService->canEndBreak($attendanceRecord);
        $canClockOut = $attendanceStatusService->canClockOut($attendanceRecord);

        return view('attendance.stamp', [
            'attendanceStatus' => $attendanceStatus,
            'canClockIn' => $canClockIn,
            'canStartBreak' => $canStartBreak,
            'canEndBreak' => $canEndBreak,
            'canClockOut' => $canClockOut,
        ]);
    }

    public function index(Request $request): View
    {
        $currentMonth = Carbon::parse($request->query('month', today()->format('Y-m')));

        $attendanceRecords = AttendanceRecord::where('user_id', $request->user()->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->with('breaks')
            ->orderBy('date')
            ->get();

        return view('attendance.index', [
            'currentMonth' => $currentMonth,
            'attendanceRecords' => $attendanceRecords,
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

    public function endBreak(
        Request $request,
        AttendanceBreakEndService $attendanceBreakEndService
    ): RedirectResponse {
        $attendanceBreakEndService->endBreak($request->user());

        return redirect('/attendance');
    }

    public function clockOut(
        Request $request,
        AttendanceClockOutService $attendanceClockOutService
    ): RedirectResponse {
        $attendanceClockOutService->clockOut($request->user());

        return redirect('/attendance');
    }
}