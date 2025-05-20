<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateField extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'certificate_fields';
    protected $fillable = [
        'certificate_id',
        'assessment_aspects_id',
        'score',
        'status'
    ];
    
    public function assessmentAspect()
{
    return $this->belongsTo(AssessmentAspect::class, 'assessment_aspects_id');  
}

}
