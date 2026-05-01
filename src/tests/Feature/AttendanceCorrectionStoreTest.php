<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 修正申請が保存される
     */
    public function test_correction_request_is_stored()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        $this->actingAs($user)->post("/attendance/detail/{$attendance->id}/correction-request", [
            'clock_in_at' => '10:00',
            'clock_out_at' => '19:00',
            'note' => '修正申請テスト',
        ]);

        $this->assertDatabaseHas('correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-04-10 10:00:00',
            'requested_clock_out_at' => '2026-04-10 19:00:00',
            'requested_note' => '修正申請テスト',
        ]);
    }
}
