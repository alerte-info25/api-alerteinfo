<?php

namespace App\Models\Soldes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoldeModels extends Model
{
    use HasFactory;

    protected $filable = [
        'montants',
        'montants_net',
        'amount_transferred',
        'slug',
    ];
}
