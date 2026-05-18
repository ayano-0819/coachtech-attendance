<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function create()
    {
        $today = Carbon::today();

        $currentDate = $today->format('Y年n月j日');
        $currentWeekday = ['日', '月', '火', '水', '木', '金', '土'][$today->dayOfWeek];
        $currentTime = now()->format('H:i');

        $attendance = Attendance::with('attendanceBreaks')
            ->where('user_id', Auth::id())
            ->whereDate('work_date', $today)
            ->first();

        $attendanceStatus = $this->getAttendanceStatus($attendance);

        return view('attendance.create', compact(
            'attendance',
            'currentDate',
            'currentWeekday',
            'currentTime'
        ))->with([
            'status' => $attendanceStatus['status'],
            'statusLabel' => $attendanceStatus['status_label'],
        ]);
    }

    public function index(Request $request)
    {
        $targetMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::today()->startOfMonth();

        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        $attendances = Attendance::with('attendanceBreaks')
            ->where('user_id', Auth::id())
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(function ($item) {
                return $item->work_date->format('Y-m-d');
            });

        $dates = [];
        $currentDate = $startOfMonth->copy();

        while ($currentDate <= $endOfMonth) {
            $dateKey = $currentDate->format('Y-m-d');

            $attendance = $attendances->get($dateKey);

            if (!$attendance) {
                $attendance = Attendance::firstOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'work_date' => $dateKey,
                    ],
                    [
                        'clock_in_at' => null,
                        'clock_out_at' => null,
                        'note' => null,
                    ]
                );
            }

            $dates[] = [
                'date' => $currentDate->copy(),

                'formatted_date' => $currentDate->format('m/d')
                    . '('
                    . ['日', '月', '火', '水', '木', '金', '土'][$currentDate->dayOfWeek]
                    . ')',

                'attendance' => $attendance,

                'break_time' => $attendance && !is_null($attendance->clock_in_at)
                    ? $attendance->break_total
                    : null,

                'work_time' => $attendance && !is_null($attendance->clock_in_at)
                    ? $attendance->work_total
                    : null,
            ];

            $currentDate->addDay();
        }

        $previousMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        return view('attendance.index', compact(
            'dates',
            'targetMonth',
            'previousMonth',
            'nextMonth'
        ));
    }
    
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'attendanceBreaks'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $pendingCorrection = CorrectionRequest::with('correctionRequestBreaks')
            ->where('attendance_id', $attendance->id)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->latest()
            ->first();

        $isPending = !is_null($pendingCorrection);

        $displayClockInAt = $isPending
            ? $pendingCorrection->requested_clock_in_at
            : $attendance->clock_in_at;

        $displayClockOutAt = $isPending
            ? $pendingCorrection->requested_clock_out_at
            : $attendance->clock_out_at;

        $displayNote = $isPending
            ? $pendingCorrection->requested_note
            : $attendance->note;

        $displayBreaks = $isPending
            ? $pendingCorrection->correctionRequestBreaks->values()
            : $attendance->attendanceBreaks->values();

        if (!$isPending) {
            $displayBreaks->push(null);
        }

        return view('attendance.show', compact(
            'attendance',
            'pendingCorrection',
            'isPending',
            'displayClockInAt',
            'displayClockOutAt',
            'displayNote',
            'displayBreaks'
        ));
    }

    public function clockIn()
    {
        $attendance = $this->getTodayAttendance();

        if ($attendance && !is_null($attendance->clock_in_at)) {
            return redirect()->route('attendance.create');
        }

        if ($attendance && is_null($attendance->clock_in_at)) {
            $attendance->update([
                'clock_in_at' => now(),
            ]);

            return redirect()->route('attendance.create');
        }

        Attendance::create([
            'user_id' => Auth::id(),
            'work_date' => now()->toDateString(),
            'clock_in_at' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function breakStart()
    {
        $attendance = $this->getTodayAttendance();

        if (!$attendance) {
            return redirect()->route('attendance.create');
        }

        if (!is_null($attendance->clock_out_at)) {
            return redirect()->route('attendance.create');
        }

        $onBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_end_at')
            ->exists();

        if ($onBreak) {
            return redirect()->route('attendance.create');
        }

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function breakEnd()
    {
        $attendance = $this->getTodayAttendance();

        if (!$attendance) {
            return redirect()->route('attendance.create');
        }

        if (!is_null($attendance->clock_out_at)) {
            return redirect()->route('attendance.create');
        }

        $attendanceBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_end_at')
            ->latest('break_start_at')
            ->first();

        if (!$attendanceBreak) {
            return redirect()->route('attendance.create');
        }

        $attendanceBreak->update([
            'break_end_at' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function clockOut()
    {
        $attendance = $this->getTodayAttendance();

        if (!$attendance) {
            return redirect()->route('attendance.create');
        }

        if (!is_null($attendance->clock_out_at)) {
            return redirect()->route('attendance.create');
        }

        $attendance->update([
            'clock_out_at' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    private function getAttendanceStatus(?Attendance $attendance): array
    {
        if (!$attendance || is_null($attendance->clock_in_at)) {
            return [
                'status' => 'off_work',
                'status_label' => '勤務外',
            ];
        }

        $isOnBreak = $attendance->attendanceBreaks()
            ->whereNull('break_end_at')
            ->exists();

        if (!is_null($attendance->clock_out_at)) {
            return [
                'status' => 'finished',
                'status_label' => '退勤済',
            ];
        }

        if ($isOnBreak) {
            return [
                'status' => 'on_break',
                'status_label' => '休憩中',
            ];
        }

        return [
            'status' => 'working',
            'status_label' => '出勤中',
        ];
    }

    private function getTodayAttendance(): ?Attendance
    {
        return Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', now()->toDateString())
            ->first();
    }
}
