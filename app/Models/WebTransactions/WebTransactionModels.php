<?php

namespace App\Models\WebTransactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\AbonnementsWebModels\AbonnementWebModels;

class WebTransactionModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'transaction_id',
        'montant',
        'method_payment',
        'date_transaction',
        'operations',
        'status'
    ];


    
    protected $table = 'web_transaction_models'; // Nom de la table

    // Relation avec AbonnementWebModels
    public function abonnement()
    {
        return $this->belongsTo(AbonnementWebModels::class, 'transaction_id', 'abonnement_web_code');
    }
}
