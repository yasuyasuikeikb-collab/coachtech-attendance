<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceStatusService;
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

        return view('attendance.stamp', [
            'attendanceStatus' => $attendanceStatus,
        ]);
    }
}