<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤務外の場合
     */
    public function test_status_is_off_duty()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合
     */
    public function test_status_is_working()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合
     */
    public function test_status_is_on_break()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
            'clock_out_at' => null,
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    /**
     * 退勤済の場合
     */
    public function test_status_is_finished()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
            'clock_out_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }
}
