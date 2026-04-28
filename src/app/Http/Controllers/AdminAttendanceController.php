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
        /*
        |--------------------------------------------------------------------------
        | 表示対象日を決める
        |--------------------------------------------------------------------------
        | ?date=2026-04-22 のようにURLパラメータがあればその日付を使う
        | なければ今日の日付を使う
        */
        $targetDate = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | その日の勤怠一覧を取得する
        |--------------------------------------------------------------------------
        | user: 名前表示用
        | attendanceBreaks: 休憩合計時間計算用
        */
        $attendances = Attendance::with(['user', 'attendanceBreaks'])
            ->whereDate('work_date', $targetDate->toDateString())
            ->get()
            ->map(function ($attendance) {
                /*
                |--------------------------------------------------------------------------
                | 休憩合計時間を計算する
                |--------------------------------------------------------------------------
                | break_start_at と break_end_at が両方入っている休憩だけ合計する
                */
                $breakSeconds = $attendance->attendanceBreaks->sum(function ($break) {
                    if ($break->break_start_at && $break->break_end_at) {
                        return $break->break_end_at->diffInSeconds($break->break_start_at);
                    }

                    return 0;
                });

                /*
                |--------------------------------------------------------------------------
                | 合計勤務時間を計算する
                |--------------------------------------------------------------------------
                | 出勤・退勤が両方ある場合のみ計算
                | （勤務時間）=（退勤 - 出勤）-（休憩合計）
                */
                $workSeconds = null;

                if ($attendance->clock_in_at && $attendance->clock_out_at) {
                    $workSeconds = $attendance->clock_out_at->diffInSeconds($attendance->clock_in_at) - $breakSeconds;
                }

                /*
                |--------------------------------------------------------------------------
                | Bladeで使いやすいように整形した値を追加
                |--------------------------------------------------------------------------
                */
                $attendance->formatted_break_time = $breakSeconds > 0
                    ? floor($breakSeconds / 3600) . ':' . str_pad(floor(($breakSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT)
                    : '';

                $attendance->formatted_work_time = !is_null($workSeconds) && $workSeconds >= 0
                    ? floor($workSeconds / 3600) . ':' . str_pad(floor(($workSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT)
                    : '';

                return $attendance;
            });

        /*
        |--------------------------------------------------------------------------
        | 前日・翌日の日付を作る
        |--------------------------------------------------------------------------
        */
        $previousDate = $targetDate->copy()->subDay()->toDateString();
        $nextDate = $targetDate->copy()->addDay()->toDateString();

        return view('admin.attendance.index', compact(
            'targetDate',
            'attendances',
            'previousDate',
            'nextDate'
        ));
    }

    /**
     * 勤怠詳細表示
     */
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'attendanceBreaks'])->findOrFail($id);

        // 承認待ちの修正申請があるかチェック
        $pendingCorrection = CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 0)
            ->exists();

        return view('admin.attendance.show', compact(
            'attendance',
            'pendingCorrection'
        ));
    }

    /**
    * 勤怠修正（管理者）
    */
    public function update(AdminAttendanceRequest $request, $id)
    {
        $attendance = Attendance::with('attendanceBreaks')->findOrFail($id);

        // 承認待ちの修正申請がある場合は、管理者でも直接修正できない
        $pendingCorrection = CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 0)
            ->exists();

        if ($pendingCorrection) {
            return redirect()
                ->route('admin.attendance.show', ['id' => $attendance->id])
                ->with('error', '承認待ちのため修正はできません。');
        }

        /*
        |--------------------------------------------------------------------------
        | ① 出勤・退勤 更新
        |--------------------------------------------------------------------------
        */
        if ($request->clock_in_at) {
            $attendance->clock_in_at = Carbon::parse(
                $attendance->work_date->format('Y-m-d') . ' ' . $request->clock_in_at
            );
        } else {
            $attendance->clock_in_at = null;
        }

        if ($request->clock_out_at) {
            $attendance->clock_out_at = Carbon::parse(
                $attendance->work_date->format('Y-m-d') . ' ' . $request->clock_out_at
            );
        } else {
            $attendance->clock_out_at = null;
        }

        $attendance->note = $request->note;

        $attendance->save();

        /*
        |--------------------------------------------------------------------------
        | ② 休憩更新（可変件数対応）
        |--------------------------------------------------------------------------
        | 既存の休憩データを順番で取り出し、
        | フォームから送られた breaks 配列を1件ずつ処理する
        */
        $existingBreaks = $attendance->attendanceBreaks->values();
        $submittedBreaks = $request->input('breaks', []);

        foreach ($submittedBreaks as $index => $submittedBreak) {
            $start = $submittedBreak['start'] ?? null;
            $end = $submittedBreak['end'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | 開始も終了も空なら何もしない
            |--------------------------------------------------------------------------
            | ただし既存データがある場合は削除したいかどうかで考え方が分かれるが、
            | 今回は「空なら更新しない」ではなく「空なら削除」にしておく
            */
            $existingBreak = $existingBreaks->get($index);

            if (empty($start) && empty($end)) {
                if ($existingBreak) {
                    $existingBreak->delete();
                }
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | 既存データがあれば更新、なければ新規作成
            |--------------------------------------------------------------------------
            */
            $attendanceBreak = $existingBreak ?? new AttendanceBreak();
            $attendanceBreak->attendance_id = $attendance->id;

            $attendanceBreak->break_start_at = $start
                ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $start)
                : null;

            $attendanceBreak->break_end_at = $end
                ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $end)
                : null;

            $attendanceBreak->save();
        }

        /*
        |--------------------------------------------------------------------------
        | ③ 余分な既存休憩データを削除
        |--------------------------------------------------------------------------
        | 例えば以前は3件あったが、今回2件しか送られてこなかった場合などに対応
        */
        if ($existingBreaks->count() > count($submittedBreaks)) {
            for ($i = count($submittedBreaks); $i < $existingBreaks->count(); $i++) {
                $extraBreak = $existingBreaks->get($i);

                if ($extraBreak) {
                    $extraBreak->delete();
                }
            }
        }

        return redirect()
            ->route('admin.attendance.show', ['id' => $attendance->id]);
    }

    /**
    * スタッフ別勤怠一覧表示
    */
    public function staffAttendance(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::now();
    
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $attendanceRecords = Attendance::with('attendanceBreaks')
            ->where('user_id', $user->id)
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->get()
            ->keyBy(function ($attendance) {
                return $attendance->work_date->format('Y-m-d');
            });

        $attendances = collect();

        for (
            $date = $currentMonth->copy()->startOfMonth();
            date->lte($currentMonth->copy()->endOfMonth());
            $date->addDay()
        ) {
            $dateKey = $date->format('Y-m-d');

            $attendance = $attendanceRecords->get($dateKey);

            if (!$attendance) {
                $attendance = new Attendance();
                $attendance->work_date = $date->copy();
                $attendance->clock_in_at = null;
                $attendance->clock_out_at = null;
                $attendance->break_total = '';
                $attendance->work_total = '';
                $attendance->setRelation('attendanceBreaks', collect());

                $attendances->push($attendance);
                continue;
            }

            $breakMinutes = 0;

            foreach ($attendance->attendanceBreaks as $break) {
                if ($break->break_start_at && $break->break_end_at) {
                    $breakMinutes += $break->break_start_at->diffInMinutes($break->break_end_at);
                }
            }

            $workMinutes = null;

            if ($attendance->clock_in_at && $attendance->clock_out_at) {
                $workMinutes = $attendance->clock_in_at->diffInMinutes($attendance->clock_out_at) - $breakMinutes;
            }

            $attendance->break_total = $breakMinutes > 0
                ? sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60)
                : '';

            $attendance->work_total = $workMinutes !== null
                ? sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60)
                : '';

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
}
