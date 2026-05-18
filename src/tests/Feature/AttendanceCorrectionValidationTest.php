<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_after_clock_out_fails()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('correction-requests.store', [
                'attendance_id' => $attendance->id,
            ]), [
                'clock_in_at' => '20:00',
                'clock_out_at' => '10:00',
                'note' => '修正',
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_break_start_after_clock_out_fails()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('correction-requests.store', [
                'attendance_id' => $attendance->id,
            ]), [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'break_start_at' => '19:00',
                        'break_end_at' => '20:00',
                    ],
                ],
                'note' => '修正',
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_break_end_after_clock_out_fails()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('correction-requests.store', [
                'attendance_id' => $attendance->id,
            ]), [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'break_start_at' => '17:00',
                        'break_end_at' => '19:00',
                    ],
                ],
                'note' => '修正',
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_note_is_required()
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 10, 9, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('correction-requests.store', [
                'attendance_id' => $attendance->id,
            ]), [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'note' => '',
            ]);

        $response->assertSessionHasErrors();
    }
}
