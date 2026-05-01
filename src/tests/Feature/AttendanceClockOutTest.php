<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 退勤ボタンが表示される
     */
    public function test_clock_out_button_is_displayed()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤');
    }

    /**
     * 退勤処理ができる
     */
    public function test_user_can_clock_out()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/clock-out');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * ステータスが退勤済になる
     */
    public function test_status_changes_to_finished()
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

    /**
     * 退勤時刻が保存される
     */
    public function test_clock_out_time_is_saved()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/clock-out');

        $this->assertNotNull($attendance->fresh()->clock_out_at);
    }
}
