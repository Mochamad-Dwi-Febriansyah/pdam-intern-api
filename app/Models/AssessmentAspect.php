<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AssessmentAspect extends Model
{
    use HasUuids;

    protected $table = 'assessment_aspects';
    protected $fillable = [
        'code_field',
        'name_field',
        'status'
    ];
}
