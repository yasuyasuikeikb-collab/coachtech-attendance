<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_休憩ボタンが正しく機能する(): void
    {
        Carbon::setTestNow('2026-06-24 12:00:00');

        $user = $this->createUser();
        $this->createWorkingRecord($user);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('休憩入');

        $response = $this->actingAs($user)->post('/attendance/break-start');

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_breaks', [
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSeeText('休憩中');
    }

    public function test_休憩は一日に何回でもできる(): void
    {
        $user = $this->createUser();
        $this->createWorkingRecord($user);

        Carbon::setTestNow('2026-06-24 12:00:00');
        $this->actingAs($user)->post('/attendance/break-start');

        Carbon::setTestNow('2026-06-24 13:00:00');
        $this->actingAs($user)->post('/attendance/break-end');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('出勤中');
        $response->assertSeeText('休憩入');
    }

    public function test_休憩戻ボタンが正しく機能する(): void
    {
        Carbon::setTestNow('2026-06-24 13:00:00');

        $user = $this->createUser();
        $attendanceRecord = $this->createWorkingRecord($user);

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('休憩戻');

        $response = $this->actingAs($user)->post('/attendance/break-end');

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSeeText('出勤中');
    }

    public function test_休憩戻は一日に何回でもできる(): void
    {
        $user = $this->createUser();
        $this->createWorkingRecord($user);

        Carbon::setTestNow('2026-06-24 12:00:00');
        $this->actingAs($user)->post('/attendance/break-start');

        Carbon::setTestNow('2026-06-24 13:00:00');
        $this->actingAs($user)->post('/attendance/break-end');

        Carbon::setTestNow('2026-06-24 15:00:00');
        $this->actingAs($user)->post('/attendance/break-start');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('休憩中');
        $response->assertSeeText('休憩戻');
    }

    public function test_休憩時刻が勤怠一覧画面で確認できる(): void
    {
        Carbon::setTestNow('2026-06-24 13:00:00');

        $user = $this->createUser();

        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSeeText('06/24');
        $response->assertSeeText('1:00');
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

    private function createWorkingRecord(User $user): AttendanceRecord
    {
        return AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => null,
            'comment' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}