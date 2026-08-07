<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordDetailResource;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AttendanceRecordController extends Controller
{
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 20);

        $attendanceRecords = AttendanceRecord::query()
            ->with(['user', 'breaks'])
            ->when($request->filled('user_id'), function ($query) use ($request): void {
                $query->where('user_id', $request->input('user_id'));
            })
            ->when($request->filled('date'), function ($query) use ($request): void {
                $query->whereDate('date', $request->input('date'));
            })
            ->when($request->filled('month'), function ($query) use ($request): void {
                $query->whereYear('date', substr($request->input('month'), 0, 4))
                    ->whereMonth('date', substr($request->input('month'), 5, 2));
            })
            ->latest('date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    public function show(string $attendanceRecord): AttendanceRecordDetailResource|JsonResponse
    {
        $attendanceRecord = $this->findAttendanceRecordWithRelations($attendanceRecord);

        if (!$attendanceRecord) {
            return $this->notFoundResponse();
        }

        return new AttendanceRecordDetailResource($attendanceRecord);
    }

    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $userId = (int) $request->input('user_id', $request->user()->id);

        if (!$request->user()->isAdmin() && $userId !== $request->user()->id) {
            return $this->forbiddenResponse();
        }

        $attendanceRecord = DB::transaction(function () use ($request, $userId): AttendanceRecord {
            $attendanceRecord = AttendanceRecord::create([
                'user_id' => $userId,
                'date' => $request->input('date'),
                'clock_in' => $this->formatApiTime($request->input('clock_in')),
                'clock_out' => $this->formatNullableApiTime($request->input('clock_out')),
                'comment' => $request->input('comment'),
            ]);

            $this->syncBreaks($attendanceRecord, $request->input('breaks', []));

            return $attendanceRecord;
        });

        $attendanceRecord->load([
            'user',
            'breaks' => function ($query): void {
                $query->orderBy('break_in');
            },
            'correctionRequests',
        ]);

        return (new AttendanceRecordDetailResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateAttendanceRecordRequest $request,
        string $attendanceRecord
    ): AttendanceRecordDetailResource|JsonResponse {
        $attendanceRecord = AttendanceRecord::with('breaks')->find($attendanceRecord);

        if (!$attendanceRecord) {
            return $this->notFoundResponse();
        }

        if (!$request->user()->can('update', $attendanceRecord)) {
            return $this->forbiddenResponse();
        }

        DB::transaction(function () use ($request, $attendanceRecord): void {
            $attendanceRecord->update([
                'date' => $request->input('date'),
                'clock_in' => $this->formatApiTime($request->input('clock_in')),
                'clock_out' => $this->formatNullableApiTime($request->input('clock_out')),
                'comment' => $request->input('comment'),
            ]);

            $attendanceRecord->breaks()->delete();
            $this->syncBreaks($attendanceRecord, $request->input('breaks', []));
        });

        $attendanceRecord = $this->findAttendanceRecordWithRelations((string) $attendanceRecord->id);

        return new AttendanceRecordDetailResource($attendanceRecord);
    }

    public function destroy(Request $request, string $attendanceRecord): JsonResponse
    {
        $attendanceRecord = AttendanceRecord::find($attendanceRecord);

        if (!$attendanceRecord) {
            return $this->notFoundResponse();
        }

        if (!$request->user()->can('delete', $attendanceRecord)) {
            return $this->forbiddenResponse();
        }

        $attendanceRecord->delete();

        return response()->json(null, 204);
    }

    private function findAttendanceRecordWithRelations(string $attendanceRecord): ?AttendanceRecord
    {
        return AttendanceRecord::with([
            'user',
            'breaks' => function ($query): void {
                $query->orderBy('break_in');
            },
            'correctionRequests' => function ($query): void {
                $query->latest();
            },
        ])->find($attendanceRecord);
    }

    private function syncBreaks(AttendanceRecord $attendanceRecord, array $breaks): void
    {
        foreach ($breaks as $break) {
            $breakIn = $break['break_in'] ?? null;
            $breakOut = $break['break_out'] ?? null;

            if (!$breakIn && !$breakOut) {
                continue;
            }

            $attendanceRecord->breaks()->create([
                'break_in' => $this->formatApiTime($breakIn),
                'break_out' => $this->formatNullableApiTime($breakOut),
            ]);
        }
    }

    private function formatApiTime(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function formatNullableApiTime(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        return $this->formatApiTime($time);
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'error' => '勤怠情報が見つかりませんでした。',
        ], 404);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'この操作を実行する権限がありません。',
        ], 403);
    }
}