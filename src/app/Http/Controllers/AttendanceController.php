<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\StoreCorrectionRequest;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceBreakEndService;
use App\Services\Attendance\AttendanceBreakStartService;
use App\Services\Attendance\AttendanceClockInService;
use App\Services\Attendance\AttendanceClockOutService;
use App\Services\Attendance\AttendanceCorrectionRequestService;
use App\Services\Attendance\AttendanceStatusService;
use App\Services\Attendance\AttendanceTimeService;
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

    public function index(
        Request $request,
        AttendanceTimeService $attendanceTimeService
    ): View {
        $currentMonth = Carbon::parse($request->query('month', today()->format('Y-m')));

        $attendanceRecords = AttendanceRecord::where('user_id', $request->user()->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->with('breaks')
            ->orderBy('date')
            ->get();

        $attendanceRows = $attendanceRecords->map(function (
            AttendanceRecord $attendanceRecord
        ) use ($attendanceTimeService): array {
            $totalBreakMinutes = $attendanceTimeService->getTotalBreakMinutes($attendanceRecord);
            $totalWorkMinutes = $attendanceTimeService->getTotalWorkMinutes($attendanceRecord);

            return [
                'id' => $attendanceRecord->id,
                'date' => $attendanceRecord->date->format('m/d'),
                'clockIn' => $attendanceRecord->clock_in ? substr($attendanceRecord->clock_in, 0, 5) : '',
                'clockOut' => $attendanceRecord->clock_out ? substr($attendanceRecord->clock_out, 0, 5) : '',
                'breakTime' => $attendanceTimeService->formatMinutes($totalBreakMinutes),
                'totalTime' => $attendanceTimeService->formatMinutes($totalWorkMinutes),
            ];
        });

        return view('attendance.index', [
            'currentMonth' => $currentMonth,
            'attendanceRows' => $attendanceRows,
        ]);
    }

    public function show(Request $request, AttendanceRecord $attendanceRecord): View
    {
        if ($attendanceRecord->user_id !== $request->user()->id) {
            abort(403);
        }

        $attendanceRecord->load(['user', 'breaks']);

        $pendingCorrectionRequest = $attendanceRecord->correctionRequests()
            ->with('correctionBreaks')
            ->where('applicant_user_id', $request->user()->id)
            ->where('status', AttendanceCorrectionRequest::STATUS_PENDING)
            ->latest()
            ->first();

        return view('attendance.show', [
            'attendanceRecord' => $attendanceRecord,
            'pendingCorrectionRequest' => $pendingCorrectionRequest,
        ]);
    }

    public function requestCorrection(
        StoreCorrectionRequest $request,
        AttendanceRecord $attendanceRecord,
        AttendanceCorrectionRequestService $attendanceCorrectionRequestService
    ): RedirectResponse {
        if ($attendanceRecord->user_id !== $request->user()->id) {
            abort(403);
        }

        $correctionRequest = $attendanceCorrectionRequestService->create(
            $attendanceRecord,
            $request->user(),
            $request->validated()
        );

        if (!$correctionRequest) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', '承認待ちの修正申請が既にあります。');
        }

        return redirect()
            ->back()
            ->with('success', '修正申請を送信しました。');
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