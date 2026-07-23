<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分が行った勤怠情報が全て表示されている(): void
    {
        Carbon::setTestNow('2026-06-24 09:00:00');

        $user = $this->createUser('一般ユーザー', 'user@example.com');
        $otherUser = $this->createUser('別ユーザー', 'other@example.com');

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-22',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '時差出勤',
        ]);

        AttendanceRecord::create([
            'user_id' => $otherUser->id,
            'date' => '2026-06-23',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'comment' => '他人の勤怠',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSeeText('06/21');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
        $response->assertSeeText('06/22');
        $response->assertSeeText('10:00');
        $response->assertSeeText('19:00');
        $response->assertDontSeeText('06/23');
        $response->assertDontSeeText('08:00');
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        Carbon::setTestNow('2026-06-24 09:00:00');

        $user = $this->createUser('一般ユーザー', 'user@example.com');

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSeeText('2026/06');
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        Carbon::setTestNow('2026-06-24 09:00:00');

        $user = $this->createUser('一般ユーザー', 'user@example.com');

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-05-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '前月勤務',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-05');

        $response->assertStatus(200);
        $response->assertSeeText('2026/05');
        $response->assertSeeText('05/15');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
    }

    public function test_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        Carbon::setTestNow('2026-06-24 09:00:00');

        $user = $this->createUser('一般ユーザー', 'user@example.com');

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-07-10',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '翌月勤務',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-07');

        $response->assertStatus(200);
        $response->assertSeeText('2026/07');
        $response->assertSeeText('07/10');
        $response->assertSeeText('10:00');
        $response->assertSeeText('19:00');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        Carbon::setTestNow('2026-06-24 09:00:00');

        $user = $this->createUser('一般ユーザー', 'user@example.com');

        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-06-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('/attendance/' . $attendanceRecord->id, false);

        $response = $this->actingAs($user)->get('/attendance/' . $attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSeeText('勤怠詳細');
    }

    private function createUser(string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
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