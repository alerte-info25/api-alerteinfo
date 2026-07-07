<?php

namespace App\Models\Abonnements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbonnementsModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'abonne_id',
        'forfait_id',
        'date_debut',
        'date_fin',
        'payments',
        'slug'
    ];
}
