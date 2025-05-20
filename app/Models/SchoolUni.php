<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolUni extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'school_unis';
    
    protected $fillable = [
        'school_university_name',
        'school_major',
        'university_faculty',
        'university_program_study',
        'school_university_address',
        'school_university_postal_code',
        'school_university_province',
        'school_university_city',
        'school_university_district',
        'school_university_village',
        'school_university_phone_number',
        'school_university_email', 
    ];
    public function documents()
    {
        return $this->hasMany(Document::class, 'user_id', 'id');
    }
}
