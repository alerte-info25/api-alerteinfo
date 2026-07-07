<?php

namespace App\Models\PigisteModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PigisteModel extends Model
{
    use HasFactory;
    protected $table = 'pigiste_models';
    protected $fillable = [
        'uuid',
        'pigiste_first_name',
        'pigiste_last_name',
        'pigiste_email',
        'pigiste_phone',
        'pigiste_address',
        'pigiste_country',
        'pigiste_speciality',
        'pigiste_cv',
        'pigiste_comment',
        'pigiste_accept_terms'
    ];

    protected $appends = [
        'pigiste_cv_url'
    ];

    public function getPigisteCvUrlAttribute()
    {
        return $this->pigiste_cv ? asset('storage/' . $this->pigiste_cv) : null;
    }
}
