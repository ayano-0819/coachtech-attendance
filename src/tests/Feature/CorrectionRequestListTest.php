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

    /**
     * 承認待ちにログインユーザーの申請が表示される
     */
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
            'status' => 0,
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認待ちテスト');
        $response->assertSee('承認待ち');
    }

    /**
     * 承認済みに承認された申請が表示される
     */
    public function test_approved_correction_requests_are_displayed()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create([
            'role' => 1,
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
            'status' => 1,
            'admin_id' => $admin->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済みテスト');
        $response->assertSee('承認済み');
    }

    /**
     * 詳細ボタンから勤怠詳細画面へ遷移できる
     */
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
            'status' => 0,
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee(route('attendance.show', ['id' => $attendance->id]));
    }
}
