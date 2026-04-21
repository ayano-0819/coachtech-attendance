<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
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
