<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 休憩入ボタンが表示される
     */
    public function test_break_start_button_is_displayed()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩入');
    }

    /**
     * 休憩入できる
     */
    public function test_user_can_start_break()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/break-start');

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
        ]);
    }

    /**
     * 休憩中になる
     */
    public function test_status_changes_to_on_break()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    /**
     * 休憩戻できる
     */
    public function test_user_can_end_break()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/break-end');

        $this->assertNotNull($break->fresh()->break_end_at);
    }

    /**
     * 出勤中に戻る
     */
    public function test_status_returns_to_working()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
        ]);

        $break->update([
            'break_end_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    /**
     * 何回でも休憩できる
     */
    public function test_user_can_take_multiple_breaks()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/break-start');
        $this->actingAs($user)->post('/attendance/break-end');

        $this->actingAs($user)->post('/attendance/break-start');

        $this->assertEquals(2, AttendanceBreak::count());
    }

    /**
     * 休憩時刻が保存される
     */
    public function test_break_time_is_saved()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
            'clock_in_at' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/break-start');

        $break = AttendanceBreak::first();

        $this->assertNotNull($break->break_start_at);
    }
}
