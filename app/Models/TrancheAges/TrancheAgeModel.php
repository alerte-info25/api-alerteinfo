<?php

namespace App\Models\TrancheAges;

use Illuminate\Database\Eloquent\Model;

class TrancheAgeModel extends Model
{
    protected $table = 'tranche_age_models';
    protected $fillable = [
        'id',
        'tranche_code_unique',
        'tranche_name',
        'tranche_age',
        'tranche_min',
        'tranche_max',
        'slug',
    ];

    protected $casts = [
        'tranche_min' => 'integer',
        'tranche_max' => 'integer',
    ];
}
