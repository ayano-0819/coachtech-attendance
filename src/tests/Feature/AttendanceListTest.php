<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 自分の勤怠のみ表示される
     */
    public function test_only_own_attendance_is_displayed()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 11:11:00',
            'clock_out_at' => '2026-04-10 22:22:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-04');

        $response->assertStatus(200);

        // 自分の勤怠時刻は表示される
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 他人の勤怠時刻は表示されない
        $response->assertDontSee('11:11');
        $response->assertDontSee('22:22');
    }

    /**
     * 現在の月が表示される
     */
    public function test_current_month_is_displayed()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 28));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee('2026/04');

        Carbon::setTestNow();
    }

    /**
     * 前月が表示される
     */
    public function test_previous_month_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-03');

        $response->assertSee('2026/03');
    }

    /**
     * 翌月が表示される
     */
    public function test_next_month_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-05');

        $response->assertSee('2026/05');
    }

    /**
     * 詳細ページに遷移できる
     */
    public function test_can_go_to_detail_page()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee(route('attendance.show', $attendance->id));
    }
}