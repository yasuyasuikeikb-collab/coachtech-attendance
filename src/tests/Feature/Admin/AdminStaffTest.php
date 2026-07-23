<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $userA = $this->createUser('一般ユーザーA', 'user-a@example.com', false);
        $userB = $this->createUser('一般ユーザーB', 'user-b@example.com', false);

        $response = $this->actingAs($adminUser)->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSeeText($userA->name);
        $response->assertSeeText($userA->email);
        $response->assertSeeText($userB->name);
        $response->assertSeeText($userB->email);
        $response->assertDontSeeText($adminUser->email);
    }

    public function test_ユーザーの勤怠情報が正しく表示される(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $this->createAttendanceRecord($user, '2026-06-24', '09:00:00', '18:00:00');

        $response = $this->actingAs($adminUser)
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-06');

        $response->assertStatus(200);
        $response->assertSeeText('一般ユーザーさんの勤怠');
        $response->assertSeeText('2026/06');
        $response->assertSeeText('06/24');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $this->createAttendanceRecord($user, '2026-05-15', '09:00:00', '18:00:00');

        $response = $this->actingAs($adminUser)
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-05');

        $response->assertStatus(200);
        $response->assertSeeText('2026/05');
        $response->assertSeeText('05/15');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
    }

    public function test_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $this->createAttendanceRecord($user, '2026-07-10', '10:00:00', '19:00:00');

        $response = $this->actingAs($adminUser)
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-07');

        $response->assertStatus(200);
        $response->assertSeeText('2026/07');
        $response->assertSeeText('07/10');
        $response->assertSeeText('10:00');
        $response->assertSeeText('19:00');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $adminUser = $this->createUser('管理者ユーザー', 'admin@example.com', true);
        $user = $this->createUser('一般ユーザー', 'user@example.com', false);

        $attendanceRecord = $this->createAttendanceRecord($user, '2026-06-24', '09:00:00', '18:00:00');

        $response = $this->actingAs($adminUser)
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-06');

        $response->assertStatus(200);
        $response->assertSee('/admin/attendance/' . $attendanceRecord->id, false);

        $response = $this->actingAs($adminUser)->get('/admin/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSeeText('勤怠詳細');
        $response->assertSeeText('一般ユーザー');
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
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => '通常勤務',
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        return $attendanceRecord;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}