<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_承認待ちの修正申請が全て表示されている(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $userA = $this->createUser('一般ユーザーA', 'user-a@example.com', false);
        $userB = $this->createUser('一般ユーザーB', 'user-b@example.com', false);

        $attendanceRecordA = $this->createAttendanceRecord($userA, '2026-06-21');
        $attendanceRecordB = $this->createAttendanceRecord($userB, '2026-06-22');
        $attendanceRecordC = $this->createAttendanceRecord($userA, '2026-06-23');

        $this->createCorrectionRequest($userA, $attendanceRecordA, '承認待ちA', AttendanceCorrectionRequest::STATUS_PENDING);
        $this->createCorrectionRequest($userB, $attendanceRecordB, '承認待ちB', AttendanceCorrectionRequest::STATUS_PENDING);
        $this->createCorrectionRequest($userA, $attendanceRecordC, '承認済みC', AttendanceCorrectionRequest::STATUS_APPROVED, $adminUser);

        $response = $this->actingAs($adminUser)->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $response->assertSeeText('承認待ちA');
        $response->assertSeeText('承認待ちB');
        $response->assertDontSeeText('承認済みC');
    }

    public function test_承認済みの修正申請が全て表示されている(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $userA = $this->createUser('一般ユーザーA', 'user-a@example.com', false);
        $userB = $this->createUser('一般ユーザーB', 'user-b@example.com', false);

        $attendanceRecordA = $this->createAttendanceRecord($userA, '2026-06-21');
        $attendanceRecordB = $this->createAttendanceRecord($userB, '2026-06-22');
        $attendanceRecordC = $this->createAttendanceRecord($userA, '2026-06-23');

        $this->createCorrectionRequest($userA, $attendanceRecordA, '承認済みA', AttendanceCorrectionRequest::STATUS_APPROVED, $adminUser);
        $this->createCorrectionRequest($userB, $attendanceRecordB, '承認済みB', AttendanceCorrectionRequest::STATUS_APPROVED, $adminUser);
        $this->createCorrectionRequest($userA, $attendanceRecordC, '承認待ちC', AttendanceCorrectionRequest::STATUS_PENDING);

        $response = $this->actingAs($adminUser)->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSeeText('承認済みA');
        $response->assertSeeText('承認済みB');
        $response->assertDontSeeText('承認待ちC');
    }

    public function test_修正申請の詳細内容が正しく表示されている(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');
        $correctionRequest = $this->createCorrectionRequest(
            $user,
            $attendanceRecord,
            '打刻修正をお願いします',
            AttendanceCorrectionRequest::STATUS_PENDING
        );

        $response = $this->actingAs($adminUser)
            ->get('/stamp_correction_request/approve/' . $correctionRequest->id);

        $response->assertStatus(200);
        $response->assertSeeText('一般ユーザー');
        $response->assertSeeText('2026年');
        $response->assertSeeText('6月24日');
        $response->assertSeeText('09:30');
        $response->assertSeeText('18:30');
        $response->assertSeeText('12:30');
        $response->assertSeeText('13:30');
        $response->assertSeeText('打刻修正をお願いします');
    }

    public function test_修正申請の承認処理が正しく行われる(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $correctionRequest = $this->createCorrectionRequest(
            $user,
            $attendanceRecord,
            '承認テスト',
            AttendanceCorrectionRequest::STATUS_PENDING
        );

        $response = $this->from('/stamp_correction_request/approve/' . $correctionRequest->id)
            ->actingAs($adminUser)
            ->post('/stamp_correction_request/approve/' . $correctionRequest->id);

        $response->assertRedirect('/stamp_correction_request/approve/' . $correctionRequest->id);

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correctionRequest->id,
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
            'approved_by' => $adminUser->id,
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
            'comment' => '承認テスト',
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:30:00',
            'break_out' => '13:30:00',
        ]);
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
        return AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);
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