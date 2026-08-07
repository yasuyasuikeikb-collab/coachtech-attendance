<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAttendanceRecordAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_write_attendance_record_apis(): void
    {
        $user = $this->createStaffUser();

        $attendanceRecord = $this->createAttendanceRecord($user, [
            'date' => '2026-08-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '未認証確認用',
        ]);

        $postResponse = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-08-11',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '未認証POST',
        ]);

        $postResponse
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);

        $putResponse = $this->putJson('/api/v1/attendance-records/' . $attendanceRecord->id, [
            'date' => '2026-08-10',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'comment' => '未認証PUT',
        ]);

        $putResponse
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);

        $deleteResponse = $this->deleteJson('/api/v1/attendance-records/' . $attendanceRecord->id);

        $deleteResponse
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_authenticated_user_can_update_and_delete_own_attendance_record(): void
    {
        $user = $this->createStaffUser([
            'email' => 'owner@example.com',
        ]);

        Sanctum::actingAs($user);

        $attendanceRecord = $this->createAttendanceRecord($user, [
            'date' => '2026-08-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '更新前',
        ]);

        $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $putResponse = $this->putJson('/api/v1/attendance-records/' . $attendanceRecord->id, [
            'date' => '2026-08-10',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'comment' => '本人更新',
            'breaks' => [
                [
                    'break_in' => '14:00',
                    'break_out' => '15:00',
                ],
            ],
        ]);

        $putResponse
            ->assertOk()
            ->assertJsonPath('data.id', $attendanceRecord->id)
            ->assertJsonPath('data.clock_in', '10:00:00')
            ->assertJsonPath('data.clock_out', '19:00:00')
            ->assertJsonPath('data.comment', '本人更新')
            ->assertJsonPath('data.breaks.0.break_in', '14:00:00')
            ->assertJsonPath('data.breaks.0.break_out', '15:00:00');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'date' => '2026-08-10',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '本人更新',
        ]);

        $deleteResponse = $this->deleteJson('/api/v1/attendance-records/' . $attendanceRecord->id);

        $deleteResponse->assertNoContent();

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    public function test_authenticated_user_cannot_update_or_delete_other_users_attendance_record(): void
    {
        $loginUser = $this->createStaffUser([
            'name' => 'ログインユーザー',
            'email' => 'login-user@example.com',
        ]);

        $otherUser = $this->createStaffUser([
            'name' => '他ユーザー',
            'email' => 'other-user@example.com',
        ]);

        Sanctum::actingAs($loginUser);

        $otherAttendanceRecord = $this->createAttendanceRecord($otherUser, [
            'date' => '2026-08-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '他ユーザーの勤怠',
        ]);

        $putResponse = $this->putJson('/api/v1/attendance-records/' . $otherAttendanceRecord->id, [
            'date' => '2026-08-10',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'comment' => '不正更新',
        ]);

        $putResponse
            ->assertForbidden()
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $otherAttendanceRecord->id,
            'user_id' => $otherUser->id,
            'date' => '2026-08-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '他ユーザーの勤怠',
        ]);

        $deleteResponse = $this->deleteJson('/api/v1/attendance-records/' . $otherAttendanceRecord->id);

        $deleteResponse
            ->assertForbidden()
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $otherAttendanceRecord->id,
            'user_id' => $otherUser->id,
        ]);
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

    private function createAttendanceRecord(User $user, array $attributes = []): AttendanceRecord
    {
        return AttendanceRecord::create(array_merge([
            'user_id' => $user->id,
            'date' => '2026-08-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => null,
        ], $attributes));
    }
}