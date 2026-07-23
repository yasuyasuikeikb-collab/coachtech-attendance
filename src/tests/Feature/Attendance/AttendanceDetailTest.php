<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面の名前がログインユーザーの氏名になっている(): void
    {
        $user = $this->createUser();

        $attendanceRecord = $this->createAttendanceRecord($user);

        $response = $this->actingAs($user)->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSeeText('一般ユーザー');
    }

    public function test_勤怠詳細画面の日付が選択した日付になっている(): void
    {
        $user = $this->createUser();

        $attendanceRecord = $this->createAttendanceRecord($user);

        $response = $this->actingAs($user)->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSeeText('2026年');
        $response->assertSeeText('6月24日');
    }

    public function test_出勤退勤に記されている時間がログインユーザーの打刻と一致している(): void
    {
        $user = $this->createUser();

        $attendanceRecord = $this->createAttendanceRecord($user);

        $response = $this->actingAs($user)->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
    }

    public function test_休憩に記されている時間がログインユーザーの打刻と一致している(): void
    {
        $user = $this->createUser();

        $attendanceRecord = $this->createAttendanceRecord($user);

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
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

    private function createAttendanceRecord(User $user): AttendanceRecord
    {
        return AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);
    }
}