<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequest as CorrectionRequestForm;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class CorrectionRequestController extends Controller
{
    /**
     * 修正申請保存
     */
    public function store(CorrectionRequestForm $request, $attendance_id)
    {
        // 対象の勤怠を取得（ログイン中ユーザー本人の勤怠のみ）
        $attendance = Attendance::where('user_id', Auth::id())
            ->findOrFail($attendance_id);

        // すでに承認待ちの修正申請があるか確認
        $pendingCorrection = CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 0)
            ->first();

        // 承認待ちがあれば保存せず詳細画面へ戻す
        if ($pendingCorrection) {
            return redirect()
                ->route('attendance.show', $attendance->id)
                ->with('error', '承認待ちのため修正はできません。');
        }

        // 勤怠日付を取得
        $workDate = Carbon::parse($attendance->work_date)->format('Y-m-d');

        // 出勤・退勤を datetime に変換
        $requestedClockInAt = Carbon::parse($workDate . ' ' . $request->clock_in_at);
        $requestedClockOutAt = Carbon::parse($workDate . ' ' . $request->clock_out_at);

        // 修正申請本体を保存
        $correctionRequest = CorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => Auth::id(),
            'requested_clock_in_at' => $requestedClockInAt,
            'requested_clock_out_at' => $requestedClockOutAt,
            'requested_note' => $request->note,
            'status' => 0,
            'admin_id' => null,
            'approved_at' => null,
        ]);

        // 休憩修正内容を保存
        foreach ($request->breaks ?? [] as $break) {
            // 開始・終了どちらも空なら保存しない
            if (empty($break['break_start_at']) && empty($break['break_end_at'])) {
                continue;
            }

            CorrectionRequestBreak::create([
                'correction_request_id' => $correctionRequest->id,
                'requested_break_start_at' => Carbon::parse($workDate . ' ' . $break['break_start_at']),
                'requested_break_end_at' => Carbon::parse($workDate . ' ' . $break['break_end_at']),
            ]);
        }

        return redirect()
            ->route('attendance.show', $attendance->id);
    }

    /**
    * 修正申請一覧
    */
    public function index(Request $request)
    {
        // タブの状態を取得
        // クエリがなければ承認待ちを初期表示
        $status = $request->query('status', 'pending');

        // 管理者の場合：全ユーザーの修正申請を取得
        if (Auth::user()->role === 1) {
            $query = CorrectionRequest::with(['user', 'attendance']);

            // 承認済みタブの場合
            if ($status === 'approved') {
                $query->where('status', 1);
            } else {
                // 承認待ちタブの場合
                $query->where('status', 0);
            }

            $correctionRequests = $query
                ->latest()
                ->get();

            return view('admin.correction_request.index', compact(
                'correctionRequests',
                'status'
            ));
        }

        // 一般ユーザーの場合：自分の修正申請だけ取得
        $query = CorrectionRequest::with(['user', 'attendance'])
            ->where('user_id', Auth::id());

        // 承認済みタブの場合
        if ($status === 'approved') {
            $query->where('status', 1);
        } else {
            // 承認待ちタブの場合
            $query->where('status', 0);
        }

        $correctionRequests = $query
            ->latest()
            ->get();

        return view('correction_request.index', compact(
            'correctionRequests',
            'status'
        ));
    }

    public function show($id)
    {
        $correctionRequest = CorrectionRequest::with([
            'user',
            'attendance',
            'correctionRequestBreaks'
        ])->findOrFail($id);

        return view('admin.correction_request.show', compact('correctionRequest'));
    }

    /**
    * 修正申請承認
    */
    public function approve($id)
    {
        // 管理者以外はアクセス不可
        if (Auth::user()->role !== 1) {
            abort(403);
        }

        $correctionRequest = CorrectionRequest::with([
            'attendance.attendanceBreaks',
            'correctionRequestBreaks',
        ])->findOrFail($id);

        // すでに承認済みの場合は何もしない
        if ($correctionRequest->status === 1) {
            return redirect()
                ->route('correction-requests.show', $correctionRequest->id);
        }

        DB::transaction(function () use ($correctionRequest) {
            $attendance = $correctionRequest->attendance;

            // 勤怠本体を申請内容で更新
            $attendance->clock_in_at = $correctionRequest->requested_clock_in_at;
            $attendance->clock_out_at = $correctionRequest->requested_clock_out_at;
            $attendance->note = $correctionRequest->requested_note;
            $attendance->save();

            // 既存の休憩を削除
            $attendance->attendanceBreaks()->delete();

            // 申請された休憩を勤怠休憩テーブルに保存
            foreach ($correctionRequest->correctionRequestBreaks as $break) {
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $break->requested_break_start_at,
                    'break_end_at' => $break->requested_break_end_at,
                ]);
            }

            // 修正申請を承認済みに更新
            $correctionRequest->update([
                'status' => 1,
                'admin_id' => Auth::id(),
                'approved_at' => now(),
            ]);
        });

        return redirect()
            ->route('correction-requests.show', $correctionRequest->id);
    }
}
