<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectionRequestDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 修正申請詳細画面が表示される
     */
    public function test_correction_request_detail_is_displayed()
    {
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
            'requested_note' => '詳細テスト',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get("/stamp_correction_request/approve/{$correction->id}");

        $response->assertStatus(200);
        $response->assertSee('詳細テスト');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /**
     * 各申請の詳細を押すと勤怠詳細画面に遷移する
     */
    public function test_can_go_to_attendance_detail_from_correction_request()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
        ]);

        $correction = CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '2026-04-10 10:00:00',
            'requested_clock_out_at' => '2026-04-10 19:00:00',
            'requested_note' => '遷移テスト',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get("/stamp_correction_request/approve/{$correction->id}");

        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
        $response->assertSee('遷移テスト');
    }
}
