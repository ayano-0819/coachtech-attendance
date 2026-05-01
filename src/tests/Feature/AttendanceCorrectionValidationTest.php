<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出勤 > 退勤 でエラー
     */
    public function test_clock_in_after_clock_out_fails()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
        ]);

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->id}/correction-request", [
            'clock_in_at' => '20:00',
            'clock_out_at' => '10:00',
            'note' => '修正',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 休憩開始 > 退勤 でエラー
     */
    public function test_break_start_after_clock_out_fails()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
        ]);

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->id}/correction-request", [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                ['break_start_at' => '19:00', 'break_end_at' => '20:00']
            ],
            'note' => '修正',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 休憩終了 > 退勤 でエラー
     */
    public function test_break_end_after_clock_out_fails()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
        ]);

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->id}/correction-request", [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                ['break_start_at' => '17:00', 'break_end_at' => '19:00']
            ],
            'note' => '修正',
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 備考未入力でエラー
     */
    public function test_note_is_required()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now(),
        ]);

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->id}/correction-request", [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'note' => '',
        ]);

        $response->assertSessionHasErrors();
    }
}
