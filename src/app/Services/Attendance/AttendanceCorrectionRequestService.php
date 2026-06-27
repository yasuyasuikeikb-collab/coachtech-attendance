<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceCorrectionRequestService
{
    public function create(
        AttendanceRecord $attendanceRecord,
        User $user,
        array $data
    ): ?AttendanceCorrectionRequest {
        $hasPendingRequest = $attendanceRecord->correctionRequests()
            ->where('applicant_user_id', $user->id)
            ->where('status', AttendanceCorrectionRequest::STATUS_PENDING)
            ->exists();

        if ($hasPendingRequest) {
            return null;
        }

        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_record_id' => $attendanceRecord->id,
            'applicant_user_id' => $user->id,
            'requested_clock_in' => $this->formatTime($data['requested_clock_in']),
            'requested_clock_out' => $this->formatTime($data['requested_clock_out']),
            'requested_comment' => $data['requested_comment'],
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        foreach ($data['requested_breaks'] ?? [] as $index => $requestedBreak) {
            if (empty($requestedBreak['break_in']) && empty($requestedBreak['break_out'])) {
                continue;
            }

            $correctionRequest->correctionBreaks()->create([
                'break_order' => $index + 1,
                'requested_break_in' => $this->formatNullableTime($requestedBreak['break_in'] ?? null),
                'requested_break_out' => $this->formatNullableTime($requestedBreak['break_out'] ?? null),
            ]);
        }

        return $correctionRequest;
    }

    private function formatTime(string $time): string
    {
        return $time . ':00';
    }

    private function formatNullableTime(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        return $this->formatTime($time);
    }
}