<?php

namespace App\Models\AbonnementsWebModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\AbonnesWebModels\CategoriesAbonnesWebModels;

class AbonnementWebForfaitsModels extends Model
{
    use HasFactory;
    protected $fillable = [
        'category_code',
        'forfait',
        'montant',
        'duree',
        'status',
        'slug',
    ];
    public function abonnement()
    {
        return $this->hasMany(AbonnementWebModels::class, 'forfait_id');
    }

    public function categories()
    {
        return $this->belongsTo(CategoriesAbonnesWebModels::class, 'category_code', 'category_code');
    }
}

