<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\Attendance\AttendanceTimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'admin_status' => true,
        ];

        if (!Auth::attempt($credentials)) {
            return redirect()
                ->back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'ログイン情報が登録されていません',
                ]);
        }

        $request->session()->regenerate();

        return redirect('/admin/attendance/list');
    }

    public function attendanceList(
        Request $request,
        AttendanceTimeService $attendanceTimeService
    ): View {
        $this->authorizeAdmin($request);

        $targetDate = Carbon::parse(
            $request->query('date', today()->toDateString())
        );

        $attendanceRecords = AttendanceRecord::whereDate('date', $targetDate)
            ->with(['user', 'breaks'])
            ->orderBy('clock_in')
            ->get();

        $attendanceRows = $attendanceRecords->map(function (
            AttendanceRecord $attendanceRecord
        ) use ($attendanceTimeService): array {
            $totalBreakMinutes = $attendanceTimeService->getTotalBreakMinutes($attendanceRecord);
            $totalWorkMinutes = $attendanceTimeService->getTotalWorkMinutes($attendanceRecord);

            return [
                'id' => $attendanceRecord->id,
                'name' => $attendanceRecord->user?->name ?? '',
                'clockIn' => $attendanceRecord->clock_in ? substr($attendanceRecord->clock_in, 0, 5) : '',
                'clockOut' => $attendanceRecord->clock_out ? substr($attendanceRecord->clock_out, 0, 5) : '',
                'breakTime' => $attendanceTimeService->formatMinutes($totalBreakMinutes),
                'totalTime' => $attendanceTimeService->formatMinutes($totalWorkMinutes),
            ];
        });

        return view('admin.attendance.index', [
            'targetDate' => $targetDate,
            'attendanceRows' => $attendanceRows,
        ]);
    }

    public function attendanceDetail(
        Request $request,
        AttendanceRecord $attendanceRecord
    ): View {
        $this->authorizeAdmin($request);

        $attendanceRecord->load([
            'user',
            'breaks' => function ($query): void {
                $query->orderBy('break_in');
            },
        ]);

        return view('admin.attendance.show', [
            'attendanceRecord' => $attendanceRecord,
        ]);
    }

    public function updateAttendance(
        UpdateAttendanceRequest $request,
        AttendanceRecord $attendanceRecord
    ): RedirectResponse {
        $this->authorizeAdmin($request);

        DB::transaction(function () use ($request, $attendanceRecord): void {
            $attendanceRecord->update([
                'clock_in' => $this->formatTime($request->input('requested_clock_in')),
                'clock_out' => $this->formatTime($request->input('requested_clock_out')),
                'comment' => $request->input('requested_comment'),
            ]);

            $attendanceRecord->breaks()->delete();

            foreach ($request->input('requested_breaks', []) as $requestedBreak) {
                $breakIn = $requestedBreak['break_in'] ?? null;
                $breakOut = $requestedBreak['break_out'] ?? null;

                if (!$breakIn && !$breakOut) {
                    continue;
                }

                $attendanceRecord->breaks()->create([
                    'break_in' => $this->formatTime($breakIn),
                    'break_out' => $this->formatTime($breakOut),
                ]);
            }
        });

        return redirect()
            ->back()
            ->with('success', '勤怠情報を修正しました。');
    }

    public function staffList(Request $request): View
    {
        $this->authorizeAdmin($request);

        $staffUsers = User::where('admin_status', false)
            ->orderBy('id')
            ->get();

        $staffRows = $staffUsers->map(function (User $staffUser): array {
            return [
                'id' => $staffUser->id,
                'name' => $staffUser->name,
                'email' => $staffUser->email,
            ];
        });

        return view('admin.staff.index', [
            'staffRows' => $staffRows,
        ]);
    }

    public function staffAttendanceList(
        Request $request,
        User $staffUser,
        AttendanceTimeService $attendanceTimeService
    ): View {
        $this->authorizeAdmin($request);

        if ($staffUser->isAdmin()) {
            abort(404);
        }

        $currentMonth = Carbon::parse(
            $request->query('month', today()->format('Y-m'))
        );

        $attendanceRecords = AttendanceRecord::where('user_id', $staffUser->id)
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

        return view('admin.staff.attendance', [
            'staffUser' => $staffUser,
            'currentMonth' => $currentMonth,
            'attendanceRows' => $attendanceRows,
        ]);
    }

    public function downloadStaffAttendanceCsv(
        Request $request,
        User $staffUser,
        AttendanceTimeService $attendanceTimeService
    ): StreamedResponse {
        $this->authorizeAdmin($request);

        if ($staffUser->isAdmin()) {
            abort(404);
        }

        $currentMonth = $this->getCsvTargetMonth($request);

        $attendanceRecords = AttendanceRecord::where('user_id', $staffUser->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->with('breaks')
            ->orderBy('date')
            ->get();

        $fileName = sprintf(
            'attendance_%s_%s.csv',
            $staffUser->id,
            $currentMonth->format('Y-m')
        );

        return response()->streamDownload(function () use (
            $attendanceRecords,
            $staffUser,
            $attendanceTimeService
        ): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '氏名',
                '日付',
                '出勤',
                '退勤',
                '休憩',
                '合計',
                '備考',
            ]);

            foreach ($attendanceRecords as $attendanceRecord) {
                fputcsv($handle, [
                    $staffUser->name,
                    $this->formatCsvDate($attendanceRecord->date),
                    $this->formatCsvTime($attendanceRecord->clock_in),
                    $this->formatCsvTime($attendanceRecord->clock_out),
                    $attendanceTimeService->formatMinutes(
                        $attendanceTimeService->getTotalBreakMinutes($attendanceRecord)
                    ),
                    $this->formatCsvTotalTime($attendanceRecord, $attendanceTimeService),
                    $attendanceRecord->comment ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            abort(403);
        }
    }

    private function formatTime(string $time): string
    {
        return $time . ':00';
    }

    private function getCsvTargetMonth(Request $request): Carbon
    {
        $month = $request->query('month', today()->format('Y-m'));

        try {
            return Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        } catch (\Throwable $exception) {
            abort(422, '年月はYYYY-MM形式で指定してください。');
        }
    }

    private function formatCsvDate($date): string
    {
        if (!$date) {
            return '';
        }

        return Carbon::parse($date)->format('Y/m/d');
    }

    private function formatCsvTime($time): string
    {
        if (!$time) {
            return '';
        }

        return Carbon::parse($time)->format('H:i');
    }

    private function formatCsvTotalTime(
        AttendanceRecord $attendanceRecord,
        AttendanceTimeService $attendanceTimeService
    ): string {
        if (!$attendanceRecord->clock_out) {
            return '';
        }

        return $attendanceTimeService->formatMinutes(
            $attendanceTimeService->getTotalWorkMinutes($attendanceRecord)
        );
    }
}