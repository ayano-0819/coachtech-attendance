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
}
