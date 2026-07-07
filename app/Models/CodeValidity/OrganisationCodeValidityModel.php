<?php

namespace App\Models\CodeValidity;

use Illuminate\Database\Eloquent\Model;

class OrganisationCodeValidityModel extends Model
{
    protected $table = 'organisation_code_validity_models';
    protected $fillable = [
        'id',
        'validity',
        'slug',
    ];

    protected $casts = [
        'validity' => 'integer',
    ];
}
