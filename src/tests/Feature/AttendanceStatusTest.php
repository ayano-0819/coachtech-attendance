<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_off_duty()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertSee('勤務外');
    }

    public function test_status_is_working()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now(),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertSee('出勤中');
    }

    public function test_status_is_on_break()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now(),
            'clock_out_at' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertSee('休憩中');
    }

    public function test_status_is_finished()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => now(),
            'clock_out_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertSee('退勤済');
    }
}
