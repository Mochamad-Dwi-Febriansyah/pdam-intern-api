<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogActivity extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'log_activities';
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'ip_address',
        'user_agent',
        'http_method',
        'response_time_ms',
        'url',
        'status'
    ];
    protected $casts = [
        'properties' => 'array',
    ];
}
