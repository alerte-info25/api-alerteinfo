<?php

namespace App\Models\Competition;

use App\Models\Epreuve\EpreuveModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompetitionModel extends Model
{
    use SoftDeletes;
    protected $table = 'competition_models';

    protected $fillable = [
        'uuid',
        'competition_code_unique',
        'competition_name',
        'code_tech',
        'competition_code',
        'code_validity',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'competition_code_unique' => 'string',
        'competition_name' => 'string',
        'competition_date' => 'datetime',
        'competition_code' => 'string',
        'code_validity' => 'datetime',
    ];

    public function epreuves()
    {
        return $this->hasMany(EpreuveModel::class, 'competition_code_unique', 'competition_code_unique');
    }
}
