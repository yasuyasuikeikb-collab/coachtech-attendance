<?php

namespace App\Http\Controllers;

use App\Services\Attendance\AttendanceReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(Request $request, AttendanceReportService $attendanceReportService): View
    {
        $report = $attendanceReportService->make($request->user());

        return view('attendance.report', [
            'summary' => $report['summary'],
            'monthlyTrend' => $report['monthlyTrend'],
            'anomalies' => $report['anomalies'],
        ]);
    }
}