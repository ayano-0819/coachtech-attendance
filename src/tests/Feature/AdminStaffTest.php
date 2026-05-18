<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_all_users()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        User::factory()->create([
            'name' => 'ユーザー1',
            'email' => 'user1@test.com',
        ]);

        User::factory()->create([
            'name' => 'ユーザー2',
            'email' => 'user2@test.com',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.index'));

        $response->assertStatus(200);
        $response->assertSee('ユーザー1');
        $response->assertSee('user1@test.com');
        $response->assertSee('ユーザー2');
        $response->assertSee('user2@test.com');
    }

    public function test_admin_can_see_selected_user_attendance()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 12, 0, 0));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $user = User::factory()->create(['name' => '対象ユーザー']);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.staff', [
                'id' => $user->id,
                'month' => '2026-04',
            ]));

        $response->assertStatus(200);
        $response->assertSee('対象ユーザー');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_previous_month_is_displayed()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 12, 0, 0));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '2026-03-10 09:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.staff', [
                'id' => $user->id,
                'month' => '2026-03',
            ]));

        $response->assertStatus(200);
        $response->assertSee('2026/03');
    }

    public function test_next_month_is_displayed()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 12, 0, 0));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-10',
            'clock_in_at' => '2026-05-10 09:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.staff', [
                'id' => $user->id,
                'month' => '2026-05',
            ]));

        $response->assertStatus(200);
        $response->assertSee('2026/05');
    }

    public function test_can_go_to_attendance_detail()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 12, 0, 0));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',    
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.staff', [
                'id' => $user->id,
                'month' => '2026-04',
            ]));

        $response->assertStatus(200);
        $response->assertSee(route('admin.attendance.show', ['id' => $attendance->id]), false);
    }
}
