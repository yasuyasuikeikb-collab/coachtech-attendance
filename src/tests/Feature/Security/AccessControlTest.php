<?php

namespace Tests\Feature\Security;

use App\Models\AttendanceCorrectionBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_未ログインユーザーは一般勤怠画面に入れない(): void
    {
        $response = $this->get('/attendance');

        $response->assertRedirect('/login');
    }

    public function test_未ログインユーザーは一般勤怠一覧画面に入れない(): void
    {
        $response = $this->get('/attendance/list');

        $response->assertRedirect('/login');
    }

    public function test_未ログインユーザーは管理者勤怠一覧画面に入れない(): void
    {
        $response = $this->get('/admin/attendance/list');

        $response->assertRedirect('/login');
    }

    public function test_未ログインユーザーは修正申請一覧画面に入れない(): void
    {
        $response = $this->get('/stamp_correction_request/list');

        $response->assertRedirect('/login');
    }

    public function test_一般ユーザーは管理者勤怠一覧画面に入れない(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $response = $this->actingAs($user)->get('/admin/attendance/list');

        $response->assertForbidden();
    }

    public function test_一般ユーザーは管理者勤怠詳細画面に入れない(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        $response = $this->actingAs($user)->get('/admin/attendance/' . $attendanceRecord->id);

        $response->assertForbidden();
    }

    public function test_一般ユーザーは管理者スタッフ一覧画面に入れない(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $response = $this->actingAs($user)->get('/admin/staff/list');

        $response->assertForbidden();
    }

    public function test_一般ユーザーはスタッフ別勤怠一覧画面に入れない(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $otherUser = $this->createUser('別ユーザー', 'other@example.com', false);

        $response = $this->actingAs($user)->get('/admin/attendance/staff/' . $otherUser->id);

        $response->assertForbidden();
    }

    public function test_一般ユーザーは管理者用承認画面に入れない(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');
        $correctionRequest = $this->createCorrectionRequest($user, $attendanceRecord);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/approve/' . $correctionRequest->id);

        $response->assertForbidden();
    }

    public function test_一般ユーザーは管理者用承認処理を実行できない(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');
        $correctionRequest = $this->createCorrectionRequest($user, $attendanceRecord);

        $response = $this->actingAs($user)
            ->post('/stamp_correction_request/approve/' . $correctionRequest->id);

        $response->assertForbidden();

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $correctionRequest->id,
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
            'approved_by' => null,
        ]);
    }

    public function test_一般ユーザーは他人の勤怠詳細を見られない(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $otherUser = $this->createUser('別ユーザー', 'other@example.com', false);
        $otherAttendanceRecord = $this->createAttendanceRecord($otherUser, '2026-06-24');

        $response = $this->actingAs($user)->get('/attendance/' . $otherAttendanceRecord->id);

        $response->assertForbidden();
    }

    public function test_一般ユーザーは他人の勤怠に修正申請できない(): void
    {
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $otherUser = $this->createUser('別ユーザー', 'other@example.com', false);
        $otherAttendanceRecord = $this->createAttendanceRecord($otherUser, '2026-06-24');

        $response = $this->actingAs($user)
            ->post('/attendance/' . $otherAttendanceRecord->id . '/correction', [
                'requested_clock_in' => '09:30',
                'requested_clock_out' => '18:30',
                'requested_comment' => '他人の勤怠を修正',
                'requested_breaks' => [
                    [
                        'break_in' => '12:30',
                        'break_out' => '13:30',
                    ],
                ],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('attendance_correction_requests', [
            'attendance_record_id' => $otherAttendanceRecord->id,
            'applicant_user_id' => $user->id,
            'requested_comment' => '他人の勤怠を修正',
        ]);
    }

    public function test_管理者は一般ルートから他人の勤怠詳細を見られない(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        $response = $this->actingAs($adminUser)->get('/attendance/' . $attendanceRecord->id);

        $response->assertForbidden();
    }

    public function test_管理者は管理者ルートから勤怠詳細を確認できる(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24');

        $response = $this->actingAs($adminUser)->get('/admin/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSeeText('一般ユーザー');
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
        AttendanceRecord $attendanceRecord
    ): AttendanceCorrectionRequest {
        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_record_id' => $attendanceRecord->id,
            'applicant_user_id' => $user->id,
            'requested_clock_in' => '09:30:00',
            'requested_clock_out' => '18:30:00',
            'requested_comment' => '修正申請テスト',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
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