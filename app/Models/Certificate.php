<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'certificates';
    protected $fillable = [
        'user_id',
        'document_id',
        'certificate_number',
        'total_score',
        'average_score',
        'certificate_path',
        'status',
        'issued_at',
    ];
    // di app/Models/Certificate.php
    public function fields()
    {
        return $this->hasMany(CertificateField::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'id');
    }
}
