<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyReport extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'daily_reports';
    protected $fillable = [
        'user_id',
        'attendance_id',
        'title',
        'report',
        'result',
        'status',
        'rejection_note',
        'verified_by_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
