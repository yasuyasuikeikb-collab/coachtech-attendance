<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'date' => $this->formatDate($this->date),
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'breaks' => $this->breaks->map(function ($attendanceBreak): array {
                return [
                    'id' => $attendanceBreak->id,
                    'break_in' => $attendanceBreak->break_in,
                    'break_out' => $attendanceBreak->break_out,
                ];
            })->values(),
            'applications' => $this->correctionRequests->map(function ($correctionRequest): array {
                return [
                    'id' => $correctionRequest->id,
                    'applicant_user_id' => $correctionRequest->applicant_user_id,
                    'requested_clock_in' => $correctionRequest->requested_clock_in,
                    'requested_clock_out' => $correctionRequest->requested_clock_out,
                    'requested_comment' => $correctionRequest->requested_comment,
                    'status' => $correctionRequest->status,
                    'approved_by' => $correctionRequest->approved_by,
                    'approved_at' => $correctionRequest->approved_at,
                ];
            })->values(),
            'comment' => $this->comment,
        ];
    }

    private function formatDate($date): string
    {
        if (!$date) {
            return '';
        }

        return $date->format('Y-m-d');
    }
}