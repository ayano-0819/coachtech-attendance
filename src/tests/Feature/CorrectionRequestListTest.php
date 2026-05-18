<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectionRequestListTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_correction_requests_are_displayed()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-04-10 10:00:00',
            'requested_clock_out_at' => '2026-04-10 19:00:00',
            'requested_note' => '承認待ちテスト',
            'status' => CorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('correction-requests.index'));

        $response->assertStatus(200);
        $response->assertSee('承認待ちテスト');
        $response->assertSee('承認待ち');
    }

    public function test_approved_correction_requests_are_displayed()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-04-10 10:00:00',
            'requested_clock_out_at' => '2026-04-10 19:00:00',
            'requested_note' => '承認済みテスト',
            'status' => CorrectionRequest::STATUS_APPROVED,
            'admin_id' => $admin->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('correction-requests.index', ['status' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済みテスト');
        $response->assertSee('承認済み');
    }

    public function test_can_go_to_correction_request_detail()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-04-10 10:00:00',
            'requested_clock_out_at' => '2026-04-10 19:00:00',
            'requested_note' => '詳細遷移テスト',
            'status' => CorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('correction-requests.index'));

        $response->assertStatus(200);
        $response->assertSee(route('attendance.show', ['id' => $attendance->id]));
    }
}
