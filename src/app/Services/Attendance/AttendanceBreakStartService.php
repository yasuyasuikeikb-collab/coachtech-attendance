<?php

namespace App\Services\Attendance;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceBreakStartService
{
    public function startBreak(User $user): ?AttendanceBreak
    {
        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        if (!$attendanceRecord || $attendanceRecord->clock_out) {
            return null;
        }

        $openBreak = $attendanceRecord->breaks()
            ->whereNull('break_out')
            ->first();

        if ($openBreak) {
            return $openBreak;
        }

        return $attendanceRecord->breaks()->create([
            'break_in' => now()->format('H:i:s'),
        ]);
    }
}