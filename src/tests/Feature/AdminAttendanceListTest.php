<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * その日にされた全ユーザーの勤怠情報が確認できる
     */
    public function test_admin_can_see_all_users_attendance_for_the_day()
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user1 = User::factory()->create([
            'name' => '一般ユーザー1',
        ]);

        $user2 = User::factory()->create([
            'name' => '一般ユーザー2',
        ]);

        Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 10:00:00',
            'clock_out_at' => '2026-04-10 19:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-04-10');

        $response->assertStatus(200);
        $response->assertSee('一般ユーザー1');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('一般ユーザー2');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_current_date_is_displayed()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 28));

        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/04/28');

        Carbon::setTestNow();
    }

    /**
     * 前日ボタンを押した時に前日の勤怠情報が表示される
     */
    public function test_previous_day_attendance_is_displayed()
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '前日ユーザー',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-09',
            'clock_in_at' => '2026-04-09 09:09:00',
            'clock_out_at' => '2026-04-09 18:09:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-04-09');

        $response->assertStatus(200);
        $response->assertSee('2026/04/09');
        $response->assertSee('前日ユーザー');
        $response->assertSee('09:09');
    }

    /**
     * 翌日ボタンを押した時に翌日の勤怠情報が表示される
     */
    public function test_next_day_attendance_is_displayed()
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '翌日ユーザー',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-11',
            'clock_in_at' => '2026-04-11 11:11:00',
            'clock_out_at' => '2026-04-11 20:11:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-04-11');

        $response->assertStatus(200);
        $response->assertSee('2026/04/11');
        $response->assertSee('翌日ユーザー');
        $response->assertSee('11:11');
    }
}
