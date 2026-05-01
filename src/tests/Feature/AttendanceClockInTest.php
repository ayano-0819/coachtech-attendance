<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤務外のとき出勤ボタンが表示される
     */
    public function test_clock_in_button_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤');
    }

    /**
     * 出勤処理ができる
     */
    public function test_user_can_clock_in()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/attendance/clock-in');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(302);
    }

    /**
     * 出勤は1日1回のみ
     */
    public function test_user_cannot_clock_in_twice()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('attendance-create__clock-in-button', false);
    }

    /**
     * 出勤時刻が正しく保存される
     */
    public function test_clock_in_time_is_saved()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/attendance/clock-in');

        $attendance = Attendance::first();

        $this->assertNotNull($attendance->clock_in_at);
    }
}
