<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceClockInService
{
    public function clockIn(User $user): AttendanceRecord
    {
        $attendanceRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        if ($attendanceRecord) {
            return $attendanceRecord;
        }

        return AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => today()->toDateString(),
            'clock_in' => now()->format('H:i:s'),
        ]);
    }
}