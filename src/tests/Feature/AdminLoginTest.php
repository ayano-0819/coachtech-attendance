<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;


class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_required_for_admin_login()
    {
        $response = $this->post(route('admin.login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_is_required_for_admin_login()
    {
        $response = $this->post(route('admin.login'), [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_admin_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->post(route('admin.login'), [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
