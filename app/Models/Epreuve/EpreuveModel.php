<?php

namespace App\Models\Epreuve;

use App\Models\Competition\CompetitionModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EpreuveModel extends Model
{
    use SoftDeletes;
    
    protected $table = 'epreuve_models';

    protected $fillable = [
        'uuid',
        'epreuve_code_unique',
        'competition_code_unique',
        'epreuve_name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'epreuve_code_unique' => 'string',
        'competition_code_unique' => 'string',
        'epreuve_name' => 'string',
    ];


    public function competition()
    {
        return $this->belongsTo(CompetitionModel::class, 'competition_code_unique','competition_code_unique');
    }
}
