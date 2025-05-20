<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'documents';

    protected $fillable = [
        'user_id',
        'school_university_id',
        'mentor_id',
        'mentor_name',
        'mentor_rank_group',
        'mentor_position',
        'mentor_nik',
        'registration_number',
        'identity_photo',
        'application_letter',
        'accepted_letter',
        'field_letter',
        'start_date',
        'end_date',
        'work_certificate',
        'document_status',
        'verified_by_id', 
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'accepted_letter' => 'array',
        'field_letter' => 'array',
        'work_certificate' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            $document->registration_number = 'BR-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -4));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function schoolUni()
    {
        return $this->belongsTo(SchoolUni::class, 'school_university_id', 'id');
    }

}
