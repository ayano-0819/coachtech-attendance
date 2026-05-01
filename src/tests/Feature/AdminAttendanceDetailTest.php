<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_admin_can_see_selected_attendance_detail()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create(['name' => '詳細ユーザー']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
            'note' => '詳細確認',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-04-10 12:00:00',
            'break_end_at' => '2026-04-10 13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('詳細ユーザー');
        $response->assertSee('2026年');
        $response->assertSee('4月10日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    /**
     * 出勤時間が退勤時間より後の場合、エラー
     */
    public function test_clock_in_after_clock_out_fails()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        $response = $this->actingAs($admin)->post("/admin/attendance/{$attendance->id}", [
            'clock_in_at' => '20:00',
            'clock_out_at' => '10:00',
            'note' => '修正',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 休憩開始時間が退勤時間より後の場合、エラー
     */
    public function test_break_start_after_clock_out_fails()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        $response = $this->actingAs($admin)->post("/admin/attendance/{$attendance->id}", [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                [
                    'start' => '19:00',
                    'end' => '20:00',
                ],
            ],
            'note' => '修正',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 休憩終了時間が退勤時間より後の場合、エラー
     */
    public function test_break_end_after_clock_out_fails()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        $response = $this->actingAs($admin)->post("/admin/attendance/{$attendance->id}", [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                [
                    'start' => '17:00',
                    'end' => '19:00',
                ],
            ],
            'note' => '修正',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 備考未入力の場合、エラー
     */
    public function test_note_is_required()
    {
        $admin = User::factory()->create(['role' => 1]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        $response = $this->actingAs($admin)->post("/admin/attendance/{$attendance->id}", [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'note' => '',
        ]);

        $response->assertSessionHasErrors();
    }
}
