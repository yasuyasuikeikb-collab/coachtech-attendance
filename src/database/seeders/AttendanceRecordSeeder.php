<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();

        if (!$user1 || !$user2) {
            return;
        }

        $user1Record = AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => now()->subDays(2)->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $user1Record->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $user2Record = AttendanceRecord::create([
            'user_id' => $user2->id,
            'date' => now()->subDays(1)->toDateString(),
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '時差出勤',
        ]);

        $user2Record->breaks()->create([
            'break_in' => '14:00:00',
            'break_out' => '15:00:00',
        ]);
    }
}