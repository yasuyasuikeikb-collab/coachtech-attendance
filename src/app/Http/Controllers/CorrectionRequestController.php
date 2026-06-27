<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $selectedStatus = $request->query(
            'status',
            AttendanceCorrectionRequest::STATUS_PENDING
        );

        if (!in_array($selectedStatus, $this->availableStatuses(), true)) {
            $selectedStatus = AttendanceCorrectionRequest::STATUS_PENDING;
        }

        $correctionRequests = AttendanceCorrectionRequest::where(
            'applicant_user_id',
            $request->user()->id
        )
            ->where('status', $selectedStatus)
            ->with(['attendanceRecord', 'applicant'])
            ->latest()
            ->get();

        $correctionRequestRows = $correctionRequests->map(function (
            AttendanceCorrectionRequest $correctionRequest
        ): array {
            return [
                'status' => $this->formatStatus($correctionRequest->status),
                'name' => $correctionRequest->applicant->name,
                'targetDate' => $correctionRequest->attendanceRecord?->date?->format('Y/m/d') ?? '',
                'reason' => $correctionRequest->requested_comment,
                'requestedAt' => $correctionRequest->created_at?->format('Y/m/d') ?? '',
                'attendanceRecordId' => $correctionRequest->attendance_record_id,
            ];
        });

        return view('correction.index', [
            'selectedStatus' => $selectedStatus,
            'correctionRequestRows' => $correctionRequestRows,
        ]);
    }

    private function availableStatuses(): array
    {
        return [
            AttendanceCorrectionRequest::STATUS_PENDING,
            AttendanceCorrectionRequest::STATUS_APPROVED,
        ];
    }

    private function formatStatus(string $status): string
    {
        if ($status === AttendanceCorrectionRequest::STATUS_APPROVED) {
            return '承認済み';
        }

        return '承認待ち';
    }
}