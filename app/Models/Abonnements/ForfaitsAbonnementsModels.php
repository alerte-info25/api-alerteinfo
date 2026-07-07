<?php

namespace App\Models\Abonnements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForfaitsAbonnementsModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'forfait',
        'montant_forfait',
        'coupons_article',
        'duree_forfait',
        'slug'
    ];
}
