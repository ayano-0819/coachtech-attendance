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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceBreaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

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

    public function getFormattedWorkDateAttribute()
    {
        return $this->work_date
            ? $this->work_date->isoFormat('MM/DD(ddd)')
            : '';
    }

    public function getClockInTimeAttribute()
    {
        return optional($this->clock_in_at)->format('H:i');
    }

    public function getClockOutTimeAttribute()
    {
        return optional($this->clock_out_at)->format('H:i');
    }

    private function formatSecondsToTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
