<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\LoginRequest;
use App\Models\AttendanceRecord;
use App\Services\Attendance\AttendanceTimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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

    private function authorizeAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            abort(403);
        }
    }
}