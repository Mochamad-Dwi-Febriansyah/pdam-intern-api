<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasUuids, SoftDeletes;
    protected $table='attendances';
    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_in_photo',
        'check_out_time',
        'check_out_photo',
        'check_in_latitude',
        'check_out_latitude',
        'check_in_longitude',
        'check_out_longitude',
        'status',
    ]; 

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime:H:i:s',
        'check_out_time' => 'datetime:H:i:s',
        'check_in_latitude' => 'decimal:6',
        'check_out_latitude' => 'decimal:6',
        'check_in_longitude' => 'decimal:6',
        'check_out_longitude' => 'decimal:6', 
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function dailyReport()
{
    return $this->hasOne(DailyReport::class);
}
}
