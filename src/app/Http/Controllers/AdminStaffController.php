<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧表示
     */
    public function index()
    {
        $staffs = User::where('role', 0)
            ->orderBy('id')
            ->get();

        return view('admin.staff.index', compact('staffs'));
    }

    /**
    * スタッフ別勤怠CSV出力
    */
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

        $response = new StreamedResponse(function () use ($user, $currentMonth, $startOfMonth, $endOfMonth, $attendances) {
            $handle = fopen('php://output', 'w');

            // Excelで文字化けしないようにBOMを付ける
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

                fputcsv($handle, [
                    $date->format('Y/m/d') . '(' . $date->isoFormat('ddd') . ')',
                    $attendance ? optional($attendance->clock_in_at)->format('H:i') : '',
                    $attendance ? optional($attendance->clock_out_at)->format('H:i') : '',
                    $attendance ? $attendance->break_total : '',
                    $attendance ? $attendance->work_total : '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
