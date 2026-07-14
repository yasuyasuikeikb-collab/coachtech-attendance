<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->user()->isAdmin()) {
            return $this->adminIndex($request);
        }

        return $this->userIndex($request);
    }

    public function showApproval(
        Request $request,
        AttendanceCorrectionRequest $correctionRequest
    ): View {
        $this->authorizeAdmin($request);

        $correctionRequest->load([
            'attendanceRecord',
            'attendanceRecord.user',
            'correctionBreaks' => function ($query): void {
                $query->orderBy('break_order');
            },
            'applicant',
        ]);

        return view('admin.correction.show', [
            'correctionRequest' => $correctionRequest,
        ]);
    }

    public function approve(
        Request $request,
        AttendanceCorrectionRequest $correctionRequest
    ): RedirectResponse {
        $this->authorizeAdmin($request);

        if ($correctionRequest->status === AttendanceCorrectionRequest::STATUS_APPROVED) {
            return redirect()
                ->back()
                ->with('success', 'この申請は既に承認済みです。');
        }

        $correctionRequest->load(['attendanceRecord', 'correctionBreaks']);

        DB::transaction(function () use ($request, $correctionRequest): void {
            $attendanceRecord = $correctionRequest->attendanceRecord;

            $attendanceRecord->update([
                'clock_in' => $correctionRequest->requested_clock_in,
                'clock_out' => $correctionRequest->requested_clock_out,
                'comment' => $correctionRequest->requested_comment,
            ]);

            $attendanceRecord->breaks()->delete();

            foreach ($correctionRequest->correctionBreaks as $correctionBreak) {
                if (!$correctionBreak->requested_break_in && !$correctionBreak->requested_break_out) {
                    continue;
                }

                $attendanceRecord->breaks()->create([
                    'break_in' => $correctionBreak->requested_break_in,
                    'break_out' => $correctionBreak->requested_break_out,
                ]);
            }

            $correctionRequest->update([
                'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
        });

        return redirect()
            ->back()
            ->with('success', '修正申請を承認しました。');
    }

    private function userIndex(Request $request): View
    {
        $selectedStatus = $this->getSelectedStatus($request);

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
                'name' => $correctionRequest->applicant?->name ?? '',
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

    private function adminIndex(Request $request): View
    {
        $selectedStatus = $this->getSelectedStatus($request);

        $correctionRequests = AttendanceCorrectionRequest::where('status', $selectedStatus)
            ->with(['attendanceRecord', 'applicant'])
            ->latest()
            ->get();

        $correctionRequestRows = $correctionRequests->map(function (
            AttendanceCorrectionRequest $correctionRequest
        ): array {
            return [
                'id' => $correctionRequest->id,
                'status' => $this->formatStatus($correctionRequest->status),
                'name' => $correctionRequest->applicant?->name ?? '',
                'targetDate' => $correctionRequest->attendanceRecord?->date?->format('Y/m/d') ?? '',
                'reason' => $correctionRequest->requested_comment,
                'requestedAt' => $correctionRequest->created_at?->format('Y/m/d') ?? '',
            ];
        });

        return view('admin.correction.index', [
            'selectedStatus' => $selectedStatus,
            'correctionRequestRows' => $correctionRequestRows,
        ]);
    }

    private function getSelectedStatus(Request $request): string
    {
        $selectedStatus = $request->query(
            'status',
            AttendanceCorrectionRequest::STATUS_PENDING
        );

        if (!in_array($selectedStatus, $this->availableStatuses(), true)) {
            return AttendanceCorrectionRequest::STATUS_PENDING;
        }

        return $selectedStatus;
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

    private function authorizeAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            abort(403);
        }
    }
}