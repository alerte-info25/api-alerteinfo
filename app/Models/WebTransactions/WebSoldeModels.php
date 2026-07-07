<?php

namespace App\Models\WebTransactions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebSoldeModels extends Model
{
    use HasFactory;

    protected $filable = [
        'montants',
        'montants_net',
        'amount_transferred',
        'slug',
    ];
}
