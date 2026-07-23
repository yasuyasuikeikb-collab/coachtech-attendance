<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DateTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_現在の日時情報がuiと同じ形式で出力されている(): void
    {
        Carbon::setTestNow('2026-06-24 09:30:00');

        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('2026年06月24日');
        $response->assertSeeText('09:30');
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