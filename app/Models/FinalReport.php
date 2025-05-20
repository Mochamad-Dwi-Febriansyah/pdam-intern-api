<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinalReport extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'final_reports';
    protected $fillable = [ 
        'user_id',
        'document_id',
        'school_university_id',
        'title',
        'report',
        'assessment_report_file',
        'final_report_file',
        'photo',
        'video',
        'mentor_verified_by_id',
        'mentor_verification_status',
        'mentor_rejection_note',
        'hr_verified_by_id',
        'hr_verification_status',
        'hr_rejection_note'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function document()
    {
        return $this->hasOne(Document::class, 'id', 'document_id');
    }
    public function histories()
{
    return $this->hasMany(FinalReportHistori::class, 'final_report_id');
}

}
