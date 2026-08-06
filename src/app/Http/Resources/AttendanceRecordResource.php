<?php

namespace App\Http\Resources;

use App\Services\Attendance\AttendanceTimeService;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray($request): array
    {
        $attendanceTimeService = app(AttendanceTimeService::class);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'date' => $this->formatDate($this->date),
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'total_time' => $this->formatTotalTime($attendanceTimeService),
            'total_break_time' => $attendanceTimeService->formatMinutes(
                $attendanceTimeService->getTotalBreakMinutes($this->resource)
            ),
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

    private function formatTotalTime(AttendanceTimeService $attendanceTimeService): string
    {
        if (!$this->clock_out) {
            return '';
        }

        return $attendanceTimeService->formatMinutes(
            $attendanceTimeService->getTotalWorkMinutes($this->resource)
        );
    }
}