<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    private const STANDARD_WORK_MINUTES = 480;
    private const LONG_WORK_MINUTES = 600;
    private const LATE_TIME = '09:00:00';
    private const EARLY_LEAVE_TIME = '18:00:00';

    public function __construct(
        private AttendanceTimeService $attendanceTimeService
    ) {
    }

    public function make(User $user): array
    {
        $startMonth = today()->copy()->subMonthsNoOverflow(5)->startOfMonth();
        $endMonth = today()->copy()->endOfMonth();

        $attendanceRecords = $user->attendanceRecords()
            ->with('breaks')
            ->whereBetween('date', [
                $startMonth->toDateString(),
                $endMonth->toDateString(),
            ])
            ->orderBy('date')
            ->get();

        return [
            'summary' => $this->makeSummary($attendanceRecords),
            'monthlyTrend' => $this->makeMonthlyTrend($attendanceRecords, $startMonth),
            'anomalies' => $this->makeThisMonthAnomalies($attendanceRecords),
        ];
    }

    private function makeSummary(Collection $attendanceRecords): array
    {
        $completedRecords = $attendanceRecords->filter(function (AttendanceRecord $attendanceRecord): bool {
            return $attendanceRecord->clock_in && $attendanceRecord->clock_out;
        });

        $totalWorkMinutes = $completedRecords->sum(function (AttendanceRecord $attendanceRecord): int {
            return $this->attendanceTimeService->getTotalWorkMinutes($attendanceRecord);
        });

        $totalOvertimeMinutes = $completedRecords->sum(function (AttendanceRecord $attendanceRecord): int {
            return max(
                0,
                $this->attendanceTimeService->getTotalWorkMinutes($attendanceRecord) - self::STANDARD_WORK_MINUTES
            );
        });

        $averageWorkMinutes = $completedRecords->count() > 0
            ? intdiv($totalWorkMinutes, $completedRecords->count())
            : 0;

        return [
            'totalWorkTime' => $this->formatHourMinute($totalWorkMinutes),
            'totalOvertimeTime' => $this->formatHourMinute($totalOvertimeMinutes),
            'averageWorkTime' => $this->formatHourMinute($averageWorkMinutes),
        ];
    }

    private function makeMonthlyTrend(Collection $attendanceRecords, Carbon $startMonth): array
    {
        $monthlyTrend = [];

        for ($index = 0; $index < 6; $index++) {
            $targetMonth = $startMonth->copy()->addMonthsNoOverflow($index);

            $recordsInMonth = $attendanceRecords->filter(function (AttendanceRecord $attendanceRecord) use ($targetMonth): bool {
                return Carbon::parse($attendanceRecord->date)->format('Y-m') === $targetMonth->format('Y-m')
                    && $attendanceRecord->clock_in
                    && $attendanceRecord->clock_out;
            });

            $workMinutes = $recordsInMonth->sum(function (AttendanceRecord $attendanceRecord): int {
                return $this->attendanceTimeService->getTotalWorkMinutes($attendanceRecord);
            });

            $overtimeMinutes = $recordsInMonth->sum(function (AttendanceRecord $attendanceRecord): int {
                return max(
                    0,
                    $this->attendanceTimeService->getTotalWorkMinutes($attendanceRecord) - self::STANDARD_WORK_MINUTES
                );
            });

            $monthlyTrend[] = [
                'month' => $targetMonth->format('Y-m'),
                'workTime' => $this->formatHourMinute($workMinutes),
                'overtimeTime' => $this->formatHourMinute($overtimeMinutes),
            ];
        }

        return $monthlyTrend;
    }

    private function makeThisMonthAnomalies(Collection $attendanceRecords): array
    {
        $currentMonth = today()->format('Y-m');

        $thisMonthRecords = $attendanceRecords->filter(function (AttendanceRecord $attendanceRecord) use ($currentMonth): bool {
            return Carbon::parse($attendanceRecord->date)->format('Y-m') === $currentMonth;
        });

        $lateCount = $thisMonthRecords->filter(function (AttendanceRecord $attendanceRecord): bool {
            return $attendanceRecord->clock_in && $attendanceRecord->clock_in > self::LATE_TIME;
        })->count();

        $earlyLeaveCount = $thisMonthRecords->filter(function (AttendanceRecord $attendanceRecord): bool {
            return $attendanceRecord->clock_out && $attendanceRecord->clock_out < self::EARLY_LEAVE_TIME;
        })->count();

        $longWorkDayCount = $thisMonthRecords->filter(function (AttendanceRecord $attendanceRecord): bool {
            if (!$attendanceRecord->clock_in || !$attendanceRecord->clock_out) {
                return false;
            }

            return $this->attendanceTimeService->getTotalWorkMinutes($attendanceRecord) > self::LONG_WORK_MINUTES;
        })->count();

        return [
            'lateCount' => $lateCount,
            'earlyLeaveCount' => $earlyLeaveCount,
            'longWorkDayCount' => $longWorkDayCount,
        ];
    }

    private function formatHourMinute(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $hours . 'h ' . $remainingMinutes . 'm';
    }
}