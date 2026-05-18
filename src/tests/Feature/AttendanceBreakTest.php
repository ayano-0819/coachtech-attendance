<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_break_start_button_is_displayed()
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
        $response->assertSee('休憩入');

        Carbon::setTestNow();
    }

    public function test_user_can_start_break()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 12, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => '2026-04-10 09:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.breakStart'));

        $response->assertStatus(302);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-04-10 12:00:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_status_changes_to_on_break()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 12, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => '2026-04-10 09:00:00',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    public function test_user_can_end_break()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 13, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => '2026-04-10 09:00:00',
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-04-10 12:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.breakEnd'));

        $response->assertStatus(302);

        $this->assertNotNull($break->fresh()->break_end_at);

        Carbon::setTestNow();
    }

    public function test_status_returns_to_working()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 13, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => '2026-04-10 09:00:00',
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-04-10 12:00:00',
        ]);

        $break->update([
            'break_end_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    public function test_user_can_take_multiple_breaks()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 12, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => '2026-04-10 09:00:00',
        ]);

        $this->actingAs($user)
            ->post(route('attendance.breakStart'));

        Carbon::setTestNow(Carbon::create(2026, 4, 10, 13, 0, 0));

        $this->actingAs($user)
            ->post(route('attendance.breakEnd'));

        Carbon::setTestNow(Carbon::create(2026, 4, 10, 15, 0, 0));

        $this->actingAs($user)
            ->post(route('attendance.breakStart'));

        $this->assertEquals(2, AttendanceBreak::count());

        Carbon::setTestNow();
    }

    public function test_break_time_is_saved()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 12, 0, 0));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in_at' => '2026-04-10 09:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.breakStart'));

        $response->assertStatus(302);

        $break = AttendanceBreak::whereHas('attendance', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->first();

        $this->assertNotNull($break->break_start_at);

        Carbon::setTestNow();
    }
}
