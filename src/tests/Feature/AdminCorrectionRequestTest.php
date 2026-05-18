<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_correction_requests_are_displayed()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['name' => '申請ユーザー']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-04-10 10:00:00',
            'requested_clock_out_at' => '2026-04-10 19:00:00',
            'requested_note' => '承認待ちテスト',
            'status' => CorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('correction-requests.index'));

        $response->assertStatus(200);
        $response->assertSee('申請ユーザー');
        $response->assertSee('承認待ちテスト');
        $response->assertSee('承認待ち');
    }

    public function test_approved_correction_requests_are_displayed()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['name' => '承認済みユーザー']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
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

        $response = $this->actingAs($admin)
            ->get(route('correction-requests.index', ['status' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済みユーザー');
        $response->assertSee('承認済みテスト');
        $response->assertSee('承認済み');
    }

    public function test_correction_request_detail_is_displayed_correctly()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['name' => '詳細ユーザー']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        $correction = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-04-10 10:00:00',
            'requested_clock_out_at' => '2026-04-10 19:00:00',
            'requested_note' => '詳細内容テスト',
            'status' => CorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('correction-requests.show', [
                'attendance_correct_request_id' => $correction->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('詳細ユーザー');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('詳細内容テスト');
    }

    public function test_admin_can_approve_correction_request()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '2026-04-10 09:00:00',
            'clock_out_at' => '2026-04-10 18:00:00',
        ]);

        $correction = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-04-10 10:00:00',
            'requested_clock_out_at' => '2026-04-10 19:00:00',
            'requested_note' => '承認処理テスト',
            'status' => CorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('correction-requests.approve', [
                'attendance_correct_request_id' => $correction->id,
            ]));

        $response->assertStatus(302);

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correction->id,
            'status' => CorrectionRequest::STATUS_APPROVED,
            'admin_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in_at' => '2026-04-10 10:00:00',
            'clock_out_at' => '2026-04-10 19:00:00',
            'note' => '承認処理テスト',
        ]);
    }
}
