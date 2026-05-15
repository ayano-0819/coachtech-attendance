<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    
    protected $fillable = [
        'user_id',
        'attendance_id',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'requested_note',
        'status',
        'admin_id',
        'approved_at',
    ];

    protected $casts = [
        'requested_clock_in_at' => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function correctionRequestBreaks()
    {
        return $this->hasMany(CorrectionRequestBreak::class);
    }

    public function getStatusLabelAttribute()
    {
        if ($this->status === self::STATUS_PENDING) {
            return '承認待ち';
        }

        return '承認済み';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }
}
