<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 0)->get();

        foreach ($users as $user) {

            for ($day = 1; $day <= 31; $day++) {

                $date = Carbon::create(2026, 5, $day);

                // 土日はスキップ
                if ($date->isWeekend()) {
                    continue;
                }

                Attendance::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'work_date' => $date->format('Y-m-d'),
                    ],
                    [
                        'clock_in_at' => $date->copy()->setTime(9, 0),
                        'clock_out_at' => $date->copy()->setTime(18, 0),
                        'note' => null,
                    ]
                );
            }
        }
    }
}
