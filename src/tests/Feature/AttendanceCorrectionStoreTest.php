<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_request_is_stored()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->post(route('correction-requests.store', [
                'attendance_id' => $attendance->id,
            ]), [
                'clock_in_at' => '10:00',
                'clock_out_at' => '19:00',
                'note' => '修正申請テスト',
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-04-10 10:00:00',
            'requested_clock_out_at' => '2026-04-10 19:00:00',
            'requested_note' => '修正申請テスト',
            'status' => CorrectionRequest::STATUS_PENDING,
        ]);

        Carbon::setTestNow();
    }
}
