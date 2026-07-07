<?php

namespace App\Models\AbonnementsWebModels;

use Illuminate\Database\Eloquent\Model;
use App\Models\AbonnesWebModels\AbonnesWebModels;
use App\Models\WebTransactions\WebTransactionModels;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AbonnementWebModels extends Model
{
    use HasFactory;
    protected $fillable = [
        'abonnement_web_code',
        'account_code_unique',
        'forfait_id',
        'montant',
        'start_date',
        'end_date',
        'country_code',
        'customer_city',
        'customer_address',
        'customer_zip_code',
        'customer_state',
        'payments',
        'payment_reference',
        'slug',
    ];


    public function countrie()
    {
        return $this->hasMany(AbonnementWebCountrieModels::class, 'abonnement_web_code', 'abonnement_web_code');
    }
    public function forfaits()
    {
        return $this->belongsTo(AbonnementWebForfaitsModels::class, 'forfait_id');
    }

    // Relation avec WebTransactionModels
    public function transactions()
    {
        return $this->hasMany(WebTransactionModels::class, 'transaction_id', 'abonnement_web_code');
    }

    public function abonnes()
    {
        return $this->belongsTo(AbonnesWebModels::class, 'account_code_unique', 'account_code_unique');
    }

}
