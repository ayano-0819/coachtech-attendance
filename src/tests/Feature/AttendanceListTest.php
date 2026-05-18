<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

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

        $response = $this->actingAs($user)
            ->get(route('attendance.index', ['month' => '2026-04']));

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertDontSee('11:11');
        $response->assertDontSee('22:22');
    }

    public function test_current_month_is_displayed()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 28, 12, 0, 0));

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('2026/04');

        Carbon::setTestNow();
    }

    public function test_previous_month_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.index', ['month' => '2026-03']));

        $response->assertStatus(200);
        $response->assertSee('2026/03');
    }

    public function test_next_month_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.index', ['month' => '2026-05']));

        $response->assertStatus(200);
        $response->assertSee('2026/05');
    }

    public function test_can_go_to_detail_page()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index', ['month' => '2026-04']));

        $response->assertStatus(200);
        $response->assertSee(route('attendance.show', ['id' => $attendance->id]), false);
    }
}
