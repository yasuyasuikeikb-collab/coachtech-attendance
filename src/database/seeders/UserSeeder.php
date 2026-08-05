<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'user1@example.com'],
            [
                'name' => '一般ユーザー1',
                'password' => Hash::make('password'),
                'admin_status' => false,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'user2@example.com'],
            [
                'name' => '一般ユーザー2',
                'password' => Hash::make('password'),
                'admin_status' => false,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'user3@example.com'],
            [
                'name' => '管理者ユーザー',
                'password' => Hash::make('password'),
                'admin_status' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}