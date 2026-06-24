<?php

namespace App\Services\Attendance;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceBreakEndService
{
    public function endBreak(User $user): ?AttendanceBreak
    {
        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        if (!$attendanceRecord || $attendanceRecord->clock_out) {
            return null;
        }

        $openBreak = $attendanceRecord->breaks()
            ->whereNull('break_out')
            ->latest()
            ->first();

        if (!$openBreak) {
            return null;
        }

        $openBreak->update([
            'break_out' => now()->format('H:i:s'),
        ]);

        return $openBreak;
    }
}