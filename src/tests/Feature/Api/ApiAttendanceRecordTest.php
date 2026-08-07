<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_attendance_records_returns_json_list_with_data_and_meta(): void
    {
        $user = $this->createStaffUser([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
        ]);

        $attendanceRecord = $this->createAttendanceRecord($user, [
            'date' => '2026-08-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->getJson('/api/v1/attendance-records');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user_name',
                        'date',
                        'clock_in',
                        'clock_out',
                        'total_time',
                        'total_break_time',
                        'comment',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('data.0.id', $attendanceRecord->id)
            ->assertJsonPath('data.0.user_id', $user->id)
            ->assertJsonPath('data.0.user_name', '一般ユーザー1')
            ->assertJsonPath('data.0.date', '2026-08-04')
            ->assertJsonPath('data.0.clock_in', '09:00:00')
            ->assertJsonPath('data.0.clock_out', '18:00:00')
            ->assertJsonPath('data.0.total_time', '8:00')
            ->assertJsonPath('data.0.total_break_time', '1:00')
            ->assertJsonPath('data.0.comment', '通常勤務')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_get_attendance_record_returns_json_detail_with_user_breaks_and_correction_requests(): void
    {
        $user = $this->createStaffUser([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
        ]);

        $attendanceRecord = $this->createAttendanceRecord($user, [
            'date' => '2026-08-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $attendanceBreak = $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_record_id' => $attendanceRecord->id,
            'applicant_user_id' => $user->id,
            'requested_clock_in' => '09:30:00',
            'requested_clock_out' => '18:30:00',
            'requested_comment' => '打刻時間を修正したいため',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->getJson('/api/v1/attendance-records/' . $attendanceRecord->id);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user' => [
                        'id',
                        'name',
                    ],
                    'date',
                    'clock_in',
                    'clock_out',
                    'breaks' => [
                        '*' => [
                            'id',
                            'break_in',
                            'break_out',
                        ],
                    ],
                    'applications' => [
                        '*' => [
                            'id',
                            'applicant_user_id',
                            'requested_clock_in',
                            'requested_clock_out',
                            'requested_comment',
                            'status',
                            'approved_by',
                            'approved_at',
                        ],
                    ],
                    'comment',
                ],
            ])
            ->assertJsonPath('data.id', $attendanceRecord->id)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', '一般ユーザー1')
            ->assertJsonPath('data.date', '2026-08-04')
            ->assertJsonPath('data.clock_in', '09:00:00')
            ->assertJsonPath('data.clock_out', '18:00:00')
            ->assertJsonPath('data.breaks.0.id', $attendanceBreak->id)
            ->assertJsonPath('data.breaks.0.break_in', '12:00:00')
            ->assertJsonPath('data.breaks.0.break_out', '13:00:00')
            ->assertJsonPath('data.applications.0.id', $correctionRequest->id)
            ->assertJsonPath('data.applications.0.applicant_user_id', $user->id)
            ->assertJsonPath('data.applications.0.requested_clock_in', '09:30:00')
            ->assertJsonPath('data.applications.0.requested_clock_out', '18:30:00')
            ->assertJsonPath('data.applications.0.requested_comment', '打刻時間を修正したいため')
            ->assertJsonPath('data.applications.0.status', AttendanceCorrectionRequest::STATUS_PENDING)
            ->assertJsonPath('data.applications.0.approved_by', null)
            ->assertJsonPath('data.applications.0.approved_at', null)
            ->assertJsonPath('data.comment', '通常勤務');
    }

    public function test_get_attendance_record_returns_404_error_json_when_record_does_not_exist(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response
            ->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_post_attendance_record_creates_attendance_record(): void
    {
        $user = $this->createStaffUser();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-08-10',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => 'API登録',
            'breaks' => [
                [
                    'break_in' => '12:00',
                    'break_out' => '13:00',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.date', '2026-08-10')
            ->assertJsonPath('data.clock_in', '09:00:00')
            ->assertJsonPath('data.clock_out', '18:00:00')
            ->assertJsonPath('data.breaks.0.break_in', '12:00:00')
            ->assertJsonPath('data.breaks.0.break_out', '13:00:00')
            ->assertJsonPath('data.comment', 'API登録');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-08-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'API登録',
        ]);

        $attendanceRecordId = $response->json('data.id');

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendanceRecordId,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }

    public function test_post_attendance_record_returns_422_with_japanese_validation_messages(): void
    {
        $user = $this->createStaffUser();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'clock_out' => '18:00',
            'comment' => '必須項目なし',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'date',
                'clock_in',
            ])
            ->assertJsonPath('errors.date.0', '日付を入力してください。')
            ->assertJsonPath('errors.clock_in.0', '出勤時間を入力してください。');
    }

    public function test_put_attendance_record_updates_attendance_record_and_missing_record_returns_404(): void
    {
        $user = $this->createStaffUser();

        Sanctum::actingAs($user);

        $attendanceRecord = $this->createAttendanceRecord($user, [
            'date' => '2026-08-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '更新前',
        ]);

        $oldBreak = $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->putJson('/api/v1/attendance-records/' . $attendanceRecord->id, [
            'date' => '2026-08-10',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'comment' => 'API更新',
            'breaks' => [
                [
                    'break_in' => '14:00',
                    'break_out' => '15:00',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $attendanceRecord->id)
            ->assertJsonPath('data.date', '2026-08-10')
            ->assertJsonPath('data.clock_in', '10:00:00')
            ->assertJsonPath('data.clock_out', '19:00:00')
            ->assertJsonPath('data.breaks.0.break_in', '14:00:00')
            ->assertJsonPath('data.breaks.0.break_out', '15:00:00')
            ->assertJsonPath('data.comment', 'API更新');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'date' => '2026-08-10',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => 'API更新',
        ]);

        $this->assertDatabaseMissing('attendance_breaks', [
            'id' => $oldBreak->id,
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '14:00:00',
            'break_out' => '15:00:00',
        ]);

        $notFoundResponse = $this->putJson('/api/v1/attendance-records/99999', [
            'date' => '2026-08-10',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'comment' => '存在しない勤怠',
        ]);

        $notFoundResponse
            ->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_delete_attendance_record_deletes_attendance_record_and_missing_record_returns_404(): void
    {
        $user = $this->createStaffUser();

        Sanctum::actingAs($user);

        $attendanceRecord = $this->createAttendanceRecord($user, [
            'date' => '2026-08-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '削除対象',
        ]);

        $attendanceBreak = $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->deleteJson('/api/v1/attendance-records/' . $attendanceRecord->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);

        $this->assertDatabaseMissing('attendance_breaks', [
            'id' => $attendanceBreak->id,
        ]);

        $notFoundResponse = $this->deleteJson('/api/v1/attendance-records/99999');

        $notFoundResponse
            ->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    private function createStaffUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
            'email_verified_at' => now(),
        ], $attributes));
    }

    private function createAttendanceRecord(User $user, array $attributes = []): AttendanceRecord
    {
        return AttendanceRecord::create(array_merge([
            'user_id' => $user->id,
            'date' => '2026-08-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => null,
        ], $attributes));
    }
}