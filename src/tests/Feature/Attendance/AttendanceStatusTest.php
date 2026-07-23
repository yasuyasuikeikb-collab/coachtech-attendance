<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤務外の場合勤怠ステータスが正しく表示される(): void
    {
        Carbon::setTestNow('2026-06-24 08:00:00');

        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('勤務外');
    }

    public function test_出勤中の場合勤怠ステータスが正しく表示される(): void
    {
        Carbon::setTestNow('2026-06-24 09:00:00');

        $user = $this->createUser();

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => null,
            'comment' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('出勤中');
    }

    public function test_休憩中の場合勤怠ステータスが正しく表示される(): void
    {
        Carbon::setTestNow('2026-06-24 12:00:00');

        $user = $this->createUser();

        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => null,
            'comment' => null,
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('休憩中');
    }

    public function test_退勤済の場合勤怠ステータスが正しく表示される(): void
    {
        Carbon::setTestNow('2026-06-24 18:00:00');

        $user = $this->createUser();

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('退勤済');
    }

    private function createUser(): User
    {
        return User::create([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}