<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_button_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('出勤');
    }

    public function test_user_can_clock_in()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('attendance.clockIn'));

        $response->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        Carbon::setTestNow();
    }

    public function test_user_cannot_clock_in_twice()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertDontSee('attendance-create__clock-in-button', false);

        Carbon::setTestNow();
    }

    public function test_clock_in_time_is_saved()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('attendance.clockIn'));

        $response->assertStatus(302);

        $attendance = Attendance::where('user_id', $user->id)->first();

        $this->assertNotNull($attendance->clock_in_at);

        Carbon::setTestNow();
    }
}
