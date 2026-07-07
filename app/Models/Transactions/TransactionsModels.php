<?php

namespace App\Models\Transactions;

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
        'status'
    ];
}
