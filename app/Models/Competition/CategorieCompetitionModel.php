<?php

namespace App\Models\Competition;

use App\Models\TrancheAges\TrancheAgeModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategorieCompetitionModel extends Model
{
    use SoftDeletes;
    protected $table = 'categorie_competition_models';

    protected $fillable = [
        'uuid',
        'categorie_code_unique',
        'tranche_code_unique',
        'categorie_name',
        'categorie_code',
        'code_validity',
        'code_tech'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'categorie_code_unique' => 'string',
        'tranche_code_unique' => 'string',
        'categorie_name' => 'string',
        'categorie_code' => 'string',
        'code_validity' => 'datetime',
        'code_tech' => 'string',
    ];

    public function tranche()
    {
        return $this->belongsTo(TrancheAgeModel::class, 'tranche_code_unique', 'tranche_code_unique');
    }

    // Code validity is active

    public function scopeCodeValidityActive($query)
    {
        return $query->where('code_validity', '>=', now());
    }
}
