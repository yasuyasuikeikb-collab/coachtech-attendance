<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceClockOutService
{
    public function clockOut(User $user): ?AttendanceRecord
    {
        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        if (!$attendanceRecord || $attendanceRecord->clock_out) {
            return null;
        }

        $hasOpenBreak = $attendanceRecord->breaks()
            ->whereNull('break_out')
            ->exists();

        if ($hasOpenBreak) {
            return null;
        }

        $attendanceRecord->update([
            'clock_out' => now()->format('H:i:s'),
        ]);

        return $attendanceRecord;
    }
}