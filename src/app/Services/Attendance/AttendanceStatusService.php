<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;

class AttendanceStatusService
{
    public const STATUS_OFF_DUTY = '勤務外';
    public const STATUS_WORKING = '出勤中';
    public const STATUS_ON_BREAK = '休憩中';
    public const STATUS_FINISHED = '退勤済';

    public function getStatus(?AttendanceRecord $attendanceRecord): string
    {
        if (!$attendanceRecord) {
            return self::STATUS_OFF_DUTY;
        }

        if ($attendanceRecord->clock_out) {
            return self::STATUS_FINISHED;
        }

        if ($this->hasOpenBreak($attendanceRecord)) {
            return self::STATUS_ON_BREAK;
        }

        return self::STATUS_WORKING;
    }

    public function canClockIn(?AttendanceRecord $attendanceRecord): bool
    {
        return !$attendanceRecord;
    }

    private function hasOpenBreak(AttendanceRecord $attendanceRecord): bool
    {
        return $attendanceRecord->breaks()
            ->whereNull('break_out')
            ->exists();
    }
}