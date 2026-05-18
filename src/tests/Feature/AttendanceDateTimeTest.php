<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDateTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_date_and_time_are_displayed()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 28, 9, 30, 0));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertStatus(200);

        $response->assertSee('2026年4月28日');
        $response->assertSee('09:30');

        Carbon::setTestNow();
    }
}
