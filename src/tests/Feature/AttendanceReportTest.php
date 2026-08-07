<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_access_attendance_report_page(): void
    {
        $this
            ->get('/attendance/report')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_attendance_report_is_calculated_correctly(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $user = $this->createStaffUser([
            'email' => 'report-user@example.com',
        ]);

        $otherUser = $this->createStaffUser([
            'email' => 'other-user@example.com',
        ]);

        $this->createReportAttendanceRecords($user);

        $this->createAttendanceRecord($otherUser, [
            'date' => '2026-06-12',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '他ユーザーの勤怠',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/attendance/report');

        $response
            ->assertOk()
            ->assertViewIs('attendance.report')
            ->assertViewHas('summary', function (array $summary): bool {
                return $summary['totalWorkTime'] === '744h 0m'
                    && $summary['totalOvertimeTime'] === '10h 0m'
                    && $summary['averageWorkTime'] === '8h 5m';
            })
            ->assertViewHas('monthlyTrend', function (array $monthlyTrend): bool {
                return count($monthlyTrend) === 6
                    && $monthlyTrend[0]['month'] === '2026-01'
                    && $monthlyTrend[0]['workTime'] === '120h 0m'
                    && $monthlyTrend[0]['overtimeTime'] === '0h 0m'
                    && $monthlyTrend[5]['month'] === '2026-06'
                    && $monthlyTrend[5]['workTime'] === '144h 0m'
                    && $monthlyTrend[5]['overtimeTime'] === '10h 0m';
            })
            ->assertViewHas('anomalies', function (array $anomalies): bool {
                return $anomalies['lateCount'] === 2
                    && $anomalies['earlyLeaveCount'] === 1
                    && $anomalies['longWorkDayCount'] === 1;
            })
            ->assertSeeText('マイ勤怠レポート')
            ->assertSeeText('過去６ヶ月の勤怠データから集計しています。')
            ->assertSeeText('基本サマリー')
            ->assertSeeText('総労働時間')
            ->assertSeeText('744h 0m')
            ->assertSeeText('総残業時間')
            ->assertSeeText('10h 0m')
            ->assertSeeText('平均労働時間 / 日')
            ->assertSeeText('8h 5m')
            ->assertSeeText('月次推移（過去６ヶ月）')
            ->assertSeeText('2026-06')
            ->assertSeeText('144h 0m')
            ->assertSeeText('今月の異常検知')
            ->assertSeeText('遅刻回数')
            ->assertSeeText('2回')
            ->assertSeeText('早退回数')
            ->assertSeeText('1回')
            ->assertSeeText('長時間労働日数')
            ->assertSeeText('1日')
            ->assertDontSeeText('他ユーザーの勤怠');
    }

    public function test_attendance_report_is_safe_when_authenticated_user_has_no_attendance_records(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $user = $this->createStaffUser();

        $response = $this
            ->actingAs($user)
            ->get('/attendance/report');

        $response
            ->assertOk()
            ->assertViewIs('attendance.report')
            ->assertViewHas('summary', function (array $summary): bool {
                return $summary['totalWorkTime'] === '0h 0m'
                    && $summary['totalOvertimeTime'] === '0h 0m'
                    && $summary['averageWorkTime'] === '0h 0m';
            })
            ->assertViewHas('monthlyTrend', function (array $monthlyTrend): bool {
                return count($monthlyTrend) === 6
                    && $monthlyTrend[0]['month'] === '2026-01'
                    && $monthlyTrend[0]['workTime'] === '0h 0m'
                    && $monthlyTrend[5]['month'] === '2026-06'
                    && $monthlyTrend[5]['workTime'] === '0h 0m';
            })
            ->assertViewHas('anomalies', function (array $anomalies): bool {
                return $anomalies['lateCount'] === 0
                    && $anomalies['earlyLeaveCount'] === 0
                    && $anomalies['longWorkDayCount'] === 0;
            })
            ->assertSeeText('0h 0m')
            ->assertSeeText('0回')
            ->assertSeeText('0日');
    }

    private function createReportAttendanceRecords(User $user): void
    {
        foreach (range(1, 5) as $month) {
            foreach (range(1, 15) as $day) {
                $record = $this->createAttendanceRecord($user, [
                    'date' => Carbon::create(2026, $month, $day)->toDateString(),
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'comment' => '通常勤務',
                ]);

                $record->breaks()->create([
                    'break_in' => '12:00:00',
                    'break_out' => '13:00:00',
                ]);
            }
        }

        foreach (range(1, 14) as $day) {
            $record = $this->createAttendanceRecord($user, [
                'date' => Carbon::create(2026, 6, $day)->toDateString(),
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '6月通常勤務',
            ]);

            $record->breaks()->create([
                'break_in' => '12:00:00',
                'break_out' => '13:00:00',
            ]);
        }

        $this->createAttendanceRecord($user, [
            'date' => '2026-06-15',
            'clock_in' => '11:00:00',
            'clock_out' => '17:00:00',
            'comment' => '遅刻早退',
        ]);

        $lateRecord = $this->createAttendanceRecord($user, [
            'date' => '2026-06-16',
            'clock_in' => '10:30:00',
            'clock_out' => '19:30:00',
            'comment' => '遅刻',
        ]);

        $lateRecord->breaks()->create([
            'break_in' => '14:00:00',
            'break_out' => '15:00:00',
        ]);

        $longWorkRecord = $this->createAttendanceRecord($user, [
            'date' => '2026-06-17',
            'clock_in' => '00:00:00',
            'clock_out' => '19:00:00',
            'comment' => '長時間勤務',
        ]);

        $longWorkRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
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
            'date' => '2026-06-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => null,
        ], $attributes));
    }
}