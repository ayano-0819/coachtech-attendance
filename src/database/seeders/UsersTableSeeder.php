<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'test1',
                'email' => 'test1@example.com',
            ],
            [
                'name' => 'test2',
                'email' => 'test2@example.com',
            ],
            [
                'name' => 'test3',
                'email' => 'test3@example.com',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => 0,
                ]
            );
        }
    }
}
