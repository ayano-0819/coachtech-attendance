<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_out_button_is_displayed()
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
        $response->assertSee('退勤');

        Carbon::setTestNow();
    }

    public function test_user_can_clock_out()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.clockOut'));

        $response->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_status_changes_to_finished()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 18, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }

    public function test_clock_out_time_is_saved()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 18, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => '2026-04-10 09:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.clockOut'));

        $response->assertStatus(302);

        $this->assertNotNull($attendance->fresh()->clock_out_at);

        Carbon::setTestNow();
    }
}
