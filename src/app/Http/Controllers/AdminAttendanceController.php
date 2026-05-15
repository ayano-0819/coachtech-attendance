<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\AdminAttendanceRequest;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $targetDate = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();

        $attendances = Attendance::with(['user', 'attendanceBreaks'])
            ->whereDate('work_date', $targetDate->toDateString())
            ->whereNotNull('clock_in_at')
            ->get();

        $previousDate = $targetDate->copy()->subDay()->toDateString();
        $nextDate = $targetDate->copy()->addDay()->toDateString();

        return view('admin.attendance.index', compact(
            'targetDate',
            'attendances',
            'previousDate',
            'nextDate'
        ));
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'attendanceBreaks'])->findOrFail($id);

        $isPending = CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->exists();

        $displayBreaks = $attendance->attendanceBreaks->values();

        $displayBreaks->push(null);

        return view('admin.attendance.show', compact(
            'attendance',
            'isPending',
            'displayBreaks'
        ));
    }

    public function update(AdminAttendanceRequest $request, $id)
    {
        $attendance = Attendance::with('attendanceBreaks')->findOrFail($id);

        $isPending = CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->exists();

        if ($isPending) {
            return redirect()
                ->route('admin.attendance.show', ['id' => $attendance->id])
                ->with('pendingCorrectionError', '承認待ちのため修正はできません。');
        }

        $this->updateAttendanceTimes($attendance, $request);

        $this->syncAttendanceBreaks(
            $attendance,
            $request->input('breaks', [])
        );

        return redirect()
            ->route('admin.attendance.show', ['id' => $attendance->id]);
    }

    public function staffAttendance(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::now();

        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $attendances = collect();

        for (
            $loopDate = $currentMonth->copy()->startOfMonth();
            $loopDate->lte($currentMonth->copy()->endOfMonth());
            $loopDate->addDay()
        ) {
            $attendance = Attendance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'work_date' => $loopDate->format('Y-m-d'),
                ],
                [
                    'clock_in_at' => null,
                    'clock_out_at' => null,
                    'note' => null,
                ]
            );

            $attendance->load('attendanceBreaks');

            $attendances->push($attendance);
        }

        return view('admin.attendance.staff', compact(
            'user',
            'currentMonth',
            'attendances',
            'prevMonth',
            'nextMonth'
        ));
    }

    private function updateAttendanceTimes(Attendance $attendance, AdminAttendanceRequest $request): void
    {
        $workDate = $attendance->work_date->format('Y-m-d');

        $attendance->clock_in_at = $request->clock_in_at
            ? Carbon::parse($workDate . ' ' . $request->clock_in_at)
            : null;

        $attendance->clock_out_at = $request->clock_out_at
            ? Carbon::parse($workDate . ' ' . $request->clock_out_at)
            : null;

        $attendance->note = $request->note;

        $attendance->save();
    }

    private function syncAttendanceBreaks(Attendance $attendance, array $submittedBreaks): void
    {
        $workDate = $attendance->work_date->format('Y-m-d');
        $existingBreaks = $attendance->attendanceBreaks->values();

        foreach ($submittedBreaks as $index => $submittedBreak) {
            $start = $submittedBreak['start'] ?? null;
            $end = $submittedBreak['end'] ?? null;

            $existingBreak = $existingBreaks->get($index);

            if (empty($start) && empty($end)) {
                if ($existingBreak) {
                    $existingBreak->delete();
                }

                continue;
            }

            $attendanceBreak = $existingBreak ?? new AttendanceBreak();
            $attendanceBreak->attendance_id = $attendance->id;

            $attendanceBreak->break_start_at = $start
                ? Carbon::parse($workDate . ' ' . $start)
                : null;

            $attendanceBreak->break_end_at = $end
                ? Carbon::parse($workDate . ' ' . $end)
                : null;

            $attendanceBreak->save();
        }

        if ($existingBreaks->count() > count($submittedBreaks)) {
            for ($i = count($submittedBreaks); $i < $existingBreaks->count(); $i++) {
                $extraBreak = $existingBreaks->get($i);

                if ($extraBreak) {
                    $extraBreak->delete();
                }
            }
        }
    }
}
