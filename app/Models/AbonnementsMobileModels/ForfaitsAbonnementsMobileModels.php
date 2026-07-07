<?php

namespace App\Models\AbonnementsMobileModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForfaitsAbonnementsMobileModels extends Model
{
    use HasFactory;

    protected $fillable = [
        'forfait_libelle',
        'montant_forfait',
        'duree_forfait',
        'is_active',
        'slug'
    ];
}
