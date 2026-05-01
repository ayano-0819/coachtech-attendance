<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDateTimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_date_and_time_are_displayed()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 28, 9, 30, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 0,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('2026年4月28日');
        $response->assertSee('09:30');

        Carbon::setTestNow();
    }
}
