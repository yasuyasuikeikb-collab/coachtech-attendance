<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        $response = $this->from('/attendance/' . $attendanceRecord->id)
            ->actingAs($user)
            ->post('/attendance/' . $attendanceRecord->id . '/correction', [
                'requested_clock_in' => '19:00',
                'requested_clock_out' => '18:00',
                'requested_comment' => '修正します',
                'requested_breaks' => [
                    [
                        'break_in' => '12:00',
                        'break_out' => '13:00',
                    ],
                ],
            ]);

        $response->assertRedirect('/attendance/' . $attendanceRecord->id);
        $response->assertSessionHasErrors([
            'requested_clock_in' => '出勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        $response = $this->from('/attendance/' . $attendanceRecord->id)
            ->actingAs($user)
            ->post('/attendance/' . $attendanceRecord->id . '/correction', [
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '18:00',
                'requested_comment' => '修正します',
                'requested_breaks' => [
                    [
                        'break_in' => '19:00',
                        'break_out' => '19:30',
                    ],
                ],
            ]);

        $response->assertRedirect('/attendance/' . $attendanceRecord->id);
        $response->assertSessionHasErrors([
            'requested_breaks.0.break_in' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        $response = $this->from('/attendance/' . $attendanceRecord->id)
            ->actingAs($user)
            ->post('/attendance/' . $attendanceRecord->id . '/correction', [
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '18:00',
                'requested_comment' => '修正します',
                'requested_breaks' => [
                    [
                        'break_in' => '12:00',
                        'break_out' => '19:00',
                    ],
                ],
            ]);

        $response->assertRedirect('/attendance/' . $attendanceRecord->id);
        $response->assertSessionHasErrors([
            'requested_breaks.0.break_out' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_備考欄が未入力の場合エラーメッセージが表示される(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        $response = $this->from('/attendance/' . $attendanceRecord->id)
            ->actingAs($user)
            ->post('/attendance/' . $attendanceRecord->id . '/correction', [
                'requested_clock_in' => '09:00',
                'requested_clock_out' => '18:00',
                'requested_comment' => '',
                'requested_breaks' => [
                    [
                        'break_in' => '12:00',
                        'break_out' => '13:00',
                    ],
                ],
            ]);

        $response->assertRedirect('/attendance/' . $attendanceRecord->id);
        $response->assertSessionHasErrors([
            'requested_comment' => '備考を記入してください',
        ]);
    }

    public function test_修正申請処理が実行され管理者の承認画面と申請一覧画面に表示される(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        $response = $this->from('/attendance/' . $attendanceRecord->id)
            ->actingAs($user)
            ->post('/attendance/' . $attendanceRecord->id . '/correction', [
                'requested_clock_in' => '09:30',
                'requested_clock_out' => '18:30',
                'requested_comment' => '打刻修正をお願いします',
                'requested_breaks' => [
                    [
                        'break_in' => '12:30',
                        'break_out' => '13:30',
                    ],
                ],
            ]);

        $response->assertRedirect('/attendance/' . $attendanceRecord->id);

        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_record_id' => $attendanceRecord->id,
            'applicant_user_id' => $user->id,
            'requested_clock_in' => '09:30:00',
            'requested_clock_out' => '18:30:00',
            'requested_comment' => '打刻修正をお願いします',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $correctionRequest = AttendanceCorrectionRequest::latest()->first();

        $response = $this->actingAs($adminUser)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSeeText('承認待ち');
        $response->assertSeeText('一般ユーザー');
        $response->assertSeeText('打刻修正をお願いします');

        $response = $this->actingAs($adminUser)
            ->get('/stamp_correction_request/approve/' . $correctionRequest->id);

        $response->assertStatus(200);
        $response->assertSeeText('一般ユーザー');
        $response->assertSeeText('09:30');
        $response->assertSeeText('18:30');
        $response->assertSeeText('12:30');
        $response->assertSeeText('13:30');
        $response->assertSeeText('打刻修正をお願いします');
    }

    public function test_承認待ちにログインユーザーが行った申請が全て表示されている(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $attendanceRecordA = $this->createAttendanceRecord($user, '2026-06-21');
        $attendanceRecordB = $this->createAttendanceRecord($user, '2026-06-22');

        $this->createCorrectionRequest($user, $attendanceRecordA, '1件目の修正申請', AttendanceCorrectionRequest::STATUS_PENDING);
        $this->createCorrectionRequest($user, $attendanceRecordB, '2件目の修正申請', AttendanceCorrectionRequest::STATUS_PENDING);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $response->assertSeeText('1件目の修正申請');
        $response->assertSeeText('2件目の修正申請');
    }

    public function test_承認済みに管理者が承認した修正申請が全て表示されている(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);

        $attendanceRecordA = $this->createAttendanceRecord($user, '2026-06-21');
        $attendanceRecordB = $this->createAttendanceRecord($user, '2026-06-22');

        $this->createCorrectionRequest($user, $attendanceRecordA, '承認済み1件目', AttendanceCorrectionRequest::STATUS_APPROVED, $adminUser);
        $this->createCorrectionRequest($user, $attendanceRecordB, '承認済み2件目', AttendanceCorrectionRequest::STATUS_APPROVED, $adminUser);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSeeText('承認済み1件目');
        $response->assertSeeText('承認済み2件目');
    }

    public function test_各申請の詳細を押下すると勤怠詳細画面に遷移する(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        $this->createCorrectionRequest($user, $attendanceRecord, '詳細確認用の申請', AttendanceCorrectionRequest::STATUS_PENDING);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $response->assertSee('/attendance/' . $attendanceRecord->id, false);

        $response = $this->actingAs($user)->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSeeText('勤怠詳細');
    }

    private function createUser(string $name, string $email, bool $isAdmin): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'admin_status' => $isAdmin,
        ]);
    }

    private function createAttendanceRecord(User $user, string $date): AttendanceRecord
    {
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        return $attendanceRecord;
    }

    private function createCorrectionRequest(
        User $user,
        AttendanceRecord $attendanceRecord,
        string $comment,
        string $status,
        ?User $adminUser = null
    ): AttendanceCorrectionRequest {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_record_id' => $attendanceRecord->id,
            'applicant_user_id' => $user->id,
            'requested_clock_in' => '09:30:00',
            'requested_clock_out' => '18:30:00',
            'requested_comment' => $comment,
            'status' => $status,
            'approved_by' => $adminUser?->id,
            'approved_at' => $status === AttendanceCorrectionRequest::STATUS_APPROVED ? now() : null,
        ]);

        AttendanceCorrectionBreak::create([
            'attendance_correction_request_id' => $correctionRequest->id,
            'break_order' => 1,
            'requested_break_in' => '12:30:00',
            'requested_break_out' => '13:30:00',
        ]);

        return $correctionRequest;
    }
}