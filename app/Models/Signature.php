<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Signature extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'signatures';
    protected $fillable = [
        'user_id',
        'name_snapshot',
        'rank_group',
        'position',
        'nik',
        'department',
        'purposes',
        'status',
    ];

    protected $casts = [
        'purposes' => 'array',
    ];
}
