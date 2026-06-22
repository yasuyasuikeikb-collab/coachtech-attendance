<?php

namespace Database\Seeders;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceCorrectionRequestSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->first();
        $user1Record = AttendanceRecord::where('user_id', $user1?->id)->first();

        if (!$user1 || !$user1Record) {
            return;
        }

        $correctionRequest = AttendanceCorrectionRequest::create([
            'attendance_record_id' => $user1Record->id,
            'applicant_user_id' => $user1->id,
            'requested_clock_in' => '09:30:00',
            'requested_clock_out' => '18:30:00',
            'requested_comment' => '打刻時間を修正したいため',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $correctionRequest->correctionBreaks()->create([
            'break_order' => 1,
            'requested_break_in' => '12:30:00',
            'requested_break_out' => '13:30:00',
        ]);
    }
}