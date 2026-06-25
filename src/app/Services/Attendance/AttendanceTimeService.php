<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use Illuminate\Support\Carbon;

class AttendanceTimeService
{
    public function getTotalBreakMinutes(AttendanceRecord $attendanceRecord): int
    {
        return $attendanceRecord->breaks
            ->filter(function ($attendanceBreak): bool {
                return $attendanceBreak->break_in && $attendanceBreak->break_out;
            })
            ->sum(function ($attendanceBreak): int {
                return Carbon::parse($attendanceBreak->break_in)
                    ->diffInMinutes(Carbon::parse($attendanceBreak->break_out));
            });
    }

    public function getTotalWorkMinutes(AttendanceRecord $attendanceRecord): ?int
    {
        if (!$attendanceRecord->clock_out) {
            return null;
        }

        $workMinutes = Carbon::parse($attendanceRecord->clock_in)
            ->diffInMinutes(Carbon::parse($attendanceRecord->clock_out));

        return max(0, $workMinutes - $this->getTotalBreakMinutes($attendanceRecord));
    }

    public function formatMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%d:%02d', $hours, $remainingMinutes);
    }
}