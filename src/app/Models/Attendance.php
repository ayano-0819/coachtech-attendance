<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'note',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    /**
     * この勤怠を登録したユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この勤怠に紐づく休憩一覧
     */
    public function attendanceBreaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    /**
     * この勤怠に対する修正申請一覧
     */
    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    /**
     * 休憩合計時間を表示用に取得
     */
    public function getBreakTotalAttribute()
    {
        $totalSeconds = 0;

        foreach ($this->attendanceBreaks as $break) {
            if ($break->break_start_at && $break->break_end_at) {
                $totalSeconds += $break->break_start_at->diffInSeconds($break->break_end_at);
            }
        }

        return $this->formatSecondsToTime($totalSeconds);
    }

    /**
     * 勤務合計時間を表示用に取得
     */
    public function getWorkTotalAttribute()
    {
        if (!$this->clock_in_at || !$this->clock_out_at) {
            return '';
        }

        $workSeconds = $this->clock_in_at->diffInSeconds($this->clock_out_at);

        foreach ($this->attendanceBreaks as $break) {
            if ($break->break_start_at && $break->break_end_at) {
                $workSeconds -= $break->break_start_at->diffInSeconds($break->break_end_at);
            }
        }

        return $this->formatSecondsToTime($workSeconds);
    }

    /**
     * 秒数を H:i 形式に変換
     */
    private function formatSecondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
