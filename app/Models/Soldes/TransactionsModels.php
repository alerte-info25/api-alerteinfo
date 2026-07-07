<?php

namespace App\Models\Soldes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionsModels extends Model
{
    use HasFactory;
    protected $fillable = [
        'transaction_id',
        'montant',
        'method_payment',
        'date_transaction',
        'operations',
        'meta_data',
        'status'
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];
}


