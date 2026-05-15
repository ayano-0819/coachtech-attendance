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
    public function store(CorrectionRequestForm $request, $attendance_id)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->findOrFail($attendance_id);

        $pendingCorrection = CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->first();

        if ($pendingCorrection) {
            return redirect()
                ->route('attendance.show', $attendance->id)
                ->with('pendingCorrectionError', '承認待ちのため修正はできません。');
        }

        $workDate = Carbon::parse($attendance->work_date)->format('Y-m-d');

        $correctionRequest = $this->createCorrectionRequest(
            $request,
            $attendance,
            $workDate
        );

        $this->createCorrectionRequestBreaks(
            $request->breaks ?? [],
            $correctionRequest,
            $workDate
        );

        return redirect()
            ->route('attendance.show', $attendance->id);
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        if (Auth::user()->isAdmin()) {
            $query = CorrectionRequest::with(['user', 'attendance']);

            $query = $this->applyStatusFilter($query, $status);

            $correctionRequests = $query
                ->latest()
                ->get();

            return view('admin.correction_request.index', compact(
                'correctionRequests',
                'status'
            ));
        }

        $query = CorrectionRequest::with(['user', 'attendance'])
            ->where('user_id', Auth::id());

        $query = $this->applyStatusFilter($query, $status);

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

    public function approve($id)
    {
        $correctionRequest = CorrectionRequest::with([
            'attendance.attendanceBreaks',
            'correctionRequestBreaks',
        ])->findOrFail($id);

        if ($correctionRequest->status === CorrectionRequest::STATUS_APPROVED) {
            return redirect()
                ->route('correction-requests.show', $correctionRequest->id);
        }

        DB::transaction(function () use ($correctionRequest) {

            $this->applyCorrectionToAttendance($correctionRequest);

            $this->syncAttendanceBreaks($correctionRequest);

            $this->approveCorrectionRequest($correctionRequest);
        });

        return redirect()
            ->route('correction-requests.show', $correctionRequest->id);
    }

    private function createCorrectionRequest(
        CorrectionRequestForm $request,
        Attendance $attendance,
        string $workDate
    ): CorrectionRequest
    {
        return CorrectionRequest::create([
            'attendance_id' => $attendance->id,

            'user_id' => Auth::id(),

            'requested_clock_in_at' => Carbon::parse(
                $workDate . ' ' . $request->clock_in_at
            ),

            'requested_clock_out_at' => Carbon::parse(
                $workDate . ' ' . $request->clock_out_at
            ),

            'requested_note' => $request->note,

            'status' => CorrectionRequest::STATUS_PENDING,

            'admin_id' => null,

            'approved_at' => null,
        ]);
    }

    private function createCorrectionRequestBreaks(
        array $breaks,
        CorrectionRequest $correctionRequest,
        string $workDate
    ): void
    {
        foreach ($breaks as $break) {
            if (
                empty($break['break_start_at'])
                && empty($break['break_end_at'])
            ) {
                continue;
            }

            CorrectionRequestBreak::create([
                'correction_request_id' => $correctionRequest->id,

                'requested_break_start_at' => Carbon::parse(
                    $workDate . ' ' . $break['break_start_at']
                ),

                'requested_break_end_at' => Carbon::parse(
                    $workDate . ' ' . $break['break_end_at']
                ),
            ]);
        }
    }

    private function applyStatusFilter($query, string $status)
    {
        $statusValue = $status === 'approved'
            ? CorrectionRequest::STATUS_APPROVED
            : CorrectionRequest::STATUS_PENDING;

        return $query->where('status', $statusValue);
    }

    private function applyCorrectionToAttendance(
        CorrectionRequest $correctionRequest
    ): void
    {
        $attendance = $correctionRequest->attendance;

        $attendance->clock_in_at = $correctionRequest->requested_clock_in_at;

        $attendance->clock_out_at = $correctionRequest->requested_clock_out_at;

        $attendance->note = $correctionRequest->requested_note;

        $attendance->save();
    }

    private function syncAttendanceBreaks(
        CorrectionRequest $correctionRequest
    ): void
    {
        $attendance = $correctionRequest->attendance;

        $attendance->attendanceBreaks()->delete();

        foreach ($correctionRequest->correctionRequestBreaks as $break) {
            AttendanceBreak::create([
                'attendance_id' => $attendance->id,

                'break_start_at' => $break->requested_break_start_at,

                'break_end_at' => $break->requested_break_end_at,
            ]);
        }
    }

    private function approveCorrectionRequest(
        CorrectionRequest $correctionRequest
    ): void
    {
        $correctionRequest->update([
            'status' => CorrectionRequest::STATUS_APPROVED,

            'admin_id' => Auth::id(),

            'approved_at' => now(),
        ]);
    }
}
