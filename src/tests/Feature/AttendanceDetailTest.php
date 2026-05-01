<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 名前がログインユーザーになっている
     */
    public function test_name_is_logged_in_user()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        $response = $this->actingAs($user)->get("/attendance/{$attendance->id}");

        $response->assertSee($user->name);
    }

    /**
    * 日付が正しい
    */
    public function test_date_is_correct()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        $response = $this->actingAs($user)->get("/attendance/{$attendance->id}");

        $response->assertSee('2026年');
        $response->assertSee('4月10日');
    }

    /**
     * 出勤・退勤が一致
     */
    public function test_clock_in_and_out_are_correct()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        $response = $this->actingAs($user)->get("/attendance/{$attendance->id}");

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 休憩時間が一致
     */
    public function test_break_time_is_correct()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-04-10 12:00:00',
            'break_end_at' => '2026-04-10 13:00:00',
        ]);

        $response = $this->actingAs($user)->get("/attendance/{$attendance->id}");

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
