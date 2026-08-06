<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAttendanceCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_staff_attendance_csv(): void
    {
        $admin = $this->createAdminUser();
        $staffUser = $this->createStaffUser();

        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $staffUser->id,
            'date' => '2026-06-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/staff/' . $staffUser->id . '/csv?month=2026-06');

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('氏名,日付,出勤,退勤,休憩,合計,備考', $csv);
        $this->assertStringContainsString(
            '一般ユーザー,2026/06/21,09:00,18:00,1:00,8:00,通常勤務',
            $csv
        );
    }

    public function test_csv_download_is_filtered_by_month(): void
    {
        $admin = $this->createAdminUser();
        $staffUser = $this->createStaffUser();

        AttendanceRecord::create([
            'user_id' => $staffUser->id,
            'date' => '2026-06-21',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '6月勤務',
        ]);

        AttendanceRecord::create([
            'user_id' => $staffUser->id,
            'date' => '2026-07-01',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '7月勤務',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/attendance/staff/' . $staffUser->id . '/csv?month=2026-06');

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('2026/06/21', $csv);
        $this->assertStringContainsString('6月勤務', $csv);
        $this->assertStringNotContainsString('2026/07/01', $csv);
        $this->assertStringNotContainsString('7月勤務', $csv);
    }

    public function test_general_user_cannot_download_staff_attendance_csv(): void
    {
        $generalUser = $this->createStaffUser([
            'email' => 'general@example.com',
        ]);

        $staffUser = $this->createStaffUser([
            'email' => 'staff@example.com',
        ]);

        $this
            ->actingAs($generalUser)
            ->get('/admin/attendance/staff/' . $staffUser->id . '/csv?month=2026-06')
            ->assertForbidden();
    }

    public function test_admin_user_cannot_be_downloaded_as_staff_csv(): void
    {
        $admin = $this->createAdminUser();
        $anotherAdmin = $this->createAdminUser([
            'email' => 'another-admin@example.com',
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin/attendance/staff/' . $anotherAdmin->id . '/csv?month=2026-06')
            ->assertNotFound();
    }

    private function createAdminUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'admin_status' => true,
            'email_verified_at' => now(),
        ], $attributes));
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
}