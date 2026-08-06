<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordDetailResource;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        $attendanceRecord = AttendanceRecord::with([
            'user',
            'breaks' => function ($query): void {
                $query->orderBy('break_in');
            },
            'correctionRequests' => function ($query): void {
                $query->latest();
            },
        ])->find($attendanceRecord);

        if (!$attendanceRecord) {
            return response()->json([
                'error' => '勤怠情報が見つかりませんでした。',
            ], 404);
        }

        return new AttendanceRecordDetailResource($attendanceRecord);
    }
}