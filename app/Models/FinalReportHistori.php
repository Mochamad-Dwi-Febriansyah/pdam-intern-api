<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinalReportHistori extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'final_report_historis';
    protected $fillable = [
        'final_report_id',
        'updated_by',
        'title',
        'report',
        'assessment_report_file',
        'final_report_file',
        'photo',
        'video',
        'verification_type',
        'status',
        'rejection_note',
        'version_number'
    ];
    public function finalReport()
    {
        return $this->belongsTo(FinalReport::class, 'final_report_id');
    }
}
