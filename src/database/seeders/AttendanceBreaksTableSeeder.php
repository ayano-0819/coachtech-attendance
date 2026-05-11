<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AttendanceBreaksTableSeeder extends Seeder
{
    public function run(): void
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {

            AttendanceBreak::updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $attendance->work_date
                        ->copy()
                        ->setTime(12, 0),
                ],
                [
                    'break_end_at' => $attendance->work_date
                        ->copy()
                        ->setTime(13, 0),
                ]
            );
        }
    }
}
