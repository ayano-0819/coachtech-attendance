<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => '管理者ユーザー',
                'password' => Hash::make('password'),
                'role' => 1,
            ]
        );
    }
}
