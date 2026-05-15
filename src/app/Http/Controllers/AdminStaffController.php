<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffController extends Controller
{
    public function index()
    {
        $staffs = User::where('role', User::ROLE_USER)
            ->orderBy('id')
            ->get();

        return view('admin.staff.index', compact('staffs'));
    }

    public function exportCsv($id)
    {
        $user = User::findOrFail($id);

        $currentMonth = Carbon::parse(request('month', now()->format('Y-m')));

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('attendanceBreaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [
                $startOfMonth->format('Y-m-d'),
                $endOfMonth->format('Y-m-d'),
            ])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function ($attendance) {
                return $attendance->work_date->format('Y-m-d');
            });

        $fileName = $user->name . '_' . $currentMonth->format('Y-m') . '_attendance.csv';

        $response = new StreamedResponse(function () use (
            $startOfMonth,
            $endOfMonth,
            $attendances
        ) {

            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '日付',
                '出勤',
                '退勤',
                '休憩',
                '合計',
            ]);

            for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                $dateKey = $date->format('Y-m-d');
                $attendance = $attendances->get($dateKey);

                $breakTime = $attendance && !is_null($attendance->clock_in_at)
                    ? $attendance->break_total
                    : '';

                $workTime = $attendance && !is_null($attendance->clock_in_at)
                    ? $attendance->work_total
                    : '';

                fputcsv($handle, [
                    $date->format('Y/m/d') . '(' . $date->isoFormat('ddd') . ')',
                    $attendance ? optional($attendance->clock_in_at)->format('H:i') : '',
                    $attendance ? optional($attendance->clock_out_at)->format('H:i') : '',
                    $breakTime,
                    $workTime,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
