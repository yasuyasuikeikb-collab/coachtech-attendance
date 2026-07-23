<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $userA = $this->createUser('一般ユーザーA', 'user-a@example.com', false);
        $userB = $this->createUser('一般ユーザーB', 'user-b@example.com', false);
        $otherDateUser = $this->createUser('別日ユーザー', 'other-date@example.com', false);

        $this->createAttendanceRecord($userA, '2026-06-24', '09:00:00', '18:00:00');
        $this->createAttendanceRecord($userB, '2026-06-24', '10:00:00', '19:00:00');
        $this->createAttendanceRecord($otherDateUser, '2026-06-23', '08:00:00', '17:00:00');

        $response = $this->actingAs($adminUser)->get('/admin/attendance/list?date=2026-06-24');

        $response->assertStatus(200);
        $response->assertSeeText('一般ユーザーA');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
        $response->assertSeeText('一般ユーザーB');
        $response->assertSeeText('10:00');
        $response->assertSeeText('19:00');
        $response->assertDontSeeText('別日ユーザー');
    }

    public function test_遷移した際に現在の日付が表示される(): void
    {
        Carbon::setTestNow('2026-06-24 09:00:00');

        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);

        $response = $this->actingAs($adminUser)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSeeText('2026/06/24');
    }

    public function test_前日を押下した時に前の日の勤怠情報が表示される(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $this->createAttendanceRecord($user, '2026-06-23', '09:00:00', '18:00:00');
        $this->createAttendanceRecord($user, '2026-06-24', '10:00:00', '19:00:00');

        $response = $this->actingAs($adminUser)->get('/admin/attendance/list?date=2026-06-23');

        $response->assertStatus(200);
        $response->assertSeeText('2026/06/23');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
        $response->assertDontSeeText('10:00');
        $response->assertDontSeeText('19:00');
    }

    public function test_翌日を押下した時に次の日の勤怠情報が表示される(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $this->createAttendanceRecord($user, '2026-06-24', '09:00:00', '18:00:00');
        $this->createAttendanceRecord($user, '2026-06-25', '10:00:00', '19:00:00');

        $response = $this->actingAs($adminUser)->get('/admin/attendance/list?date=2026-06-25');

        $response->assertStatus(200);
        $response->assertSeeText('2026/06/25');
        $response->assertSeeText('10:00');
        $response->assertSeeText('19:00');
        $response->assertDontSeeText('09:00');
        $response->assertDontSeeText('18:00');
    }

    public function test_勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24', '09:00:00', '18:00:00');

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($adminUser)->get('/admin/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSeeText('一般ユーザー');
        $response->assertSeeText('2026年');
        $response->assertSeeText('6月24日');
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
    }

    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24', '09:00:00', '18:00:00');

        $response = $this->from('/admin/attendance/' . $attendanceRecord->id)
            ->actingAs($adminUser)
            ->post('/admin/attendance/' . $attendanceRecord->id . '/update', [
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

        $response->assertRedirect('/admin/attendance/' . $attendanceRecord->id);
        $response->assertSessionHasErrors([
            'requested_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24', '09:00:00', '18:00:00');

        $response = $this->from('/admin/attendance/' . $attendanceRecord->id)
            ->actingAs($adminUser)
            ->post('/admin/attendance/' . $attendanceRecord->id . '/update', [
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

        $response->assertRedirect('/admin/attendance/' . $attendanceRecord->id);
        $response->assertSessionHasErrors([
            'requested_breaks.0.break_in' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24', '09:00:00', '18:00:00');

        $response = $this->from('/admin/attendance/' . $attendanceRecord->id)
            ->actingAs($adminUser)
            ->post('/admin/attendance/' . $attendanceRecord->id . '/update', [
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

        $response->assertRedirect('/admin/attendance/' . $attendanceRecord->id);
        $response->assertSessionHasErrors([
            'requested_breaks.0.break_out' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_備考欄が未入力の場合エラーメッセージが表示される(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);
        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24', '09:00:00', '18:00:00');

        $response = $this->from('/admin/attendance/' . $attendanceRecord->id)
            ->actingAs($adminUser)
            ->post('/admin/attendance/' . $attendanceRecord->id . '/update', [
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

        $response->assertRedirect('/admin/attendance/' . $attendanceRecord->id);
        $response->assertSessionHasErrors([
            'requested_comment' => '備考を記入してください',
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

    private function createAttendanceRecord(
        User $user,
        string $date,
        string $clockIn,
        string $clockOut
    ): AttendanceRecord {
        return AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => '通常勤務',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}