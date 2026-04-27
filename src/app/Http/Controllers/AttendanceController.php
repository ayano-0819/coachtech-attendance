<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function create()
    {
        // 今日の日付
        $today = Carbon::today();

        // 今日の勤怠データを取得
        $attendance = Attendance::with('attendanceBreaks')
            ->where('user_id', Auth::id())
            ->whereDate('work_date', $today)
            ->first();

        // 初期ステータス
        $status = '勤務外';

        if ($attendance) {
            // 休憩中かどうか
            $isOnBreak = $attendance->attendanceBreaks()
                ->whereNull('break_end_at')
                ->exists();

            if (!is_null($attendance->clock_out_at)) {
                $status = '退勤済';
            } elseif ($isOnBreak) {
                $status = '休憩中';
            } else {
                $status = '出勤中';
            }
        }

        return view('attendance.create', compact('attendance', 'status'));
    }

    public function index(Request $request)
    {
        $targetMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::today()->startOfMonth();

        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        // 勤怠データ取得
        $attendances = Attendance::with('attendanceBreaks')
            ->where('user_id', Auth::id())
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->keyBy(function ($item) {
                return $item->work_date->format('Y-m-d');
            });

        /*
        |--------------------------------------------------------------------------
        | 月の日付一覧を作る
        |--------------------------------------------------------------------------
        */
        $dates = [];
        $currentDate = $startOfMonth->copy();

        while ($currentDate <= $endOfMonth) {
            $dateKey = $currentDate->format('Y-m-d');

            $attendance = $attendances->get($dateKey);

            // 休憩・合計計算（ある場合だけ）
            $breakTime = '';
            $workTime = '';

            if ($attendance) {
                $breakSeconds = $attendance->attendanceBreaks->sum(function ($break) {
                    if ($break->break_start_at && $break->break_end_at) {
                        return $break->break_end_at->diffInSeconds($break->break_start_at);
                    }
                    return 0;
                });

                if ($attendance->clock_in_at && $attendance->clock_out_at) {
                    $workSeconds = $attendance->clock_out_at->diffInSeconds($attendance->clock_in_at) - $breakSeconds;

                    $workTime = floor($workSeconds / 3600) . ':' . str_pad(floor(($workSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
                }

                if ($breakSeconds > 0) {
                    $breakTime = floor($breakSeconds / 3600) . ':' . str_pad(floor(($breakSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
                }
            }

            $dates[] = [
                'date' => $currentDate->copy(),
                'attendance' => $attendance,
                'break_time' => $breakTime,
                'work_time' => $workTime,
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

        $pendingCorrection = \App\Models\CorrectionRequest::with('correctionRequestBreaks')
            ->where('attendance_id', $attendance->id)
            ->where('status', 0)
            ->latest()
            ->first();

        $displayNote = $attendance->note;

        if ($pendingCorrection) {
            $attendance->clock_in_at = $pendingCorrection->requested_clock_in_at;
            $attendance->clock_out_at = $pendingCorrection->requested_clock_out_at;
            $displayNote = $pendingCorrection->requested_note;
            $attendance->setRelation('attendanceBreaks', $pendingCorrection->correctionRequestBreaks);
        }  

        return view('attendance.show', compact(
            'attendance',
            'pendingCorrection',
            'displayNote'
        ));
    }

    public function clockIn()
    {
        $today = now()->toDateString();

        // すでに出勤してないかチェック（1日1回）
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance) {
            return redirect()->back();
        }

        // 出勤レコード作成
        Attendance::create([
            'user_id' => Auth::id(),
            'work_date' => $today,
            'clock_in_at' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function breakStart()
    {  
        // 今日の勤怠データを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', now()->toDateString())
            ->first();

        // 勤怠がない場合は戻る
        if (!$attendance) {
            return redirect()->route('attendance.create');
        }

        // すでに退勤済みなら休憩開始できない
        if (!is_null($attendance->clock_out_at)) {
            return redirect()->route('attendance.create');
        }

        // 進行中の休憩があれば新しく作らない
        $onBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_end_at')
            ->exists();

        if ($onBreak) {
            return redirect()->route('attendance.create');
        }

        // 休憩開始レコードを作成
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function breakEnd()
    {
        // 今日の勤怠データを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', now()->toDateString())
            ->first();

        // 今日の勤怠がなければ戻る
        if (!$attendance) {
            return redirect()->route('attendance.create');
        }

        // すでに退勤済みなら休憩戻はできない
        if (!is_null($attendance->clock_out_at)) {
            return redirect()->route('attendance.create');
        }

        // 進行中の休憩レコードを取得
        $attendanceBreak = AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNull('break_end_at')
            ->latest('break_start_at')
            ->first();

        // 進行中の休憩がなければ戻る
        if (!$attendanceBreak) {
            return redirect()->route('attendance.create');
    }

        // 休憩終了時刻を保存
        $attendanceBreak->update([
            'break_end_at' => now(),
        ]);

        return redirect()->route('attendance.create');
    }

    public function clockOut()
    {
        // 今日の勤怠データを取得
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', now()->toDateString())
            ->first();

        // 今日の勤怠がない場合はそのまま戻る
        if (!$attendance) {
            return redirect()->route('attendance.create');
        }

        // すでに退勤済みならそのまま戻る
        if (!is_null($attendance->clock_out_at)) {
            return redirect()->route('attendance.create');
        }

        // 退勤時刻を保存
        $attendance->update([
            'clock_out_at' => now(),
        ]);

        return redirect()->route('attendance.create');
    }
}
