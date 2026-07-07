<?php

namespace App\Models\Engagements;

use Illuminate\Database\Eloquent\Model;
use App\Models\LicenceModels\LicenceModel;
use App\Models\Epreuve\EpreuveModel;
use App\Models\LigueModels\LigueModels;
use Illuminate\Database\Eloquent\SoftDeletes;

class EngagementNationalDataModel extends Model
{
    use SoftDeletes;
    protected $table = 'engagement_national_data_models';
    protected $fillable = [
        'uuid',
        'engagement_code_unique',
        'ligue_code_unique',
        'epreuve_code_unique',
        'athlete_code_unique',
        'dossars',
        'performance',
        'rang',
        'points',
    ];

    public function engagement()
    {
        return $this->belongsTo(EngagementNationalModel::class,'engagement_code_unique','engagement_code_unique');
    }

    public function epreuve()
    {
        return $this->belongsTo(EpreuveModel::class,'epreuve_code_unique','epreuve_code_unique');
    }

    public function athlete()
    {
        return $this->belongsTo(LicenceModel::class,'athlete_code_unique','licence_code_unique');
    }

    public function ligue()
    {
        return $this->belongsTo(LigueModels::class,'ligue_code_unique','ligue_code_unique');
    }

    public function createData(array $data)
    {
        return $this->create([
            'uuid' => $data['uuid'],
            'engagement_code_unique' => $data['engagement_code_unique'],
            'ligue_code_unique' => $data['ligue_code_unique'],
            'epreuve_code_unique' => $data['epreuve_code_unique'],
            'athlete_code_unique' => $data['athlete_code_unique'],
            //'dossars' => $data['dossars'],
        ]);
    }

    public function updateEngagementData(array $data, $uuid)
    {
        return $this->where('uuid', $uuid)->update([
            'ligue_code_unique' => $data['ligue_code_unique'] ?? null,
            'epreuve_code_unique' => $data['epreuve_code_unique'] ?? null,
            'athlete_code_unique' => $data['athlete_code_unique'] ?? null,
            'dossars' => $data['dossars'] ?? null,
            'performance' => $data['performance'] ?? null,
            'rang' => $data['rang'] ?? null,
            'points' => $data['points'] ?? null,
        ]);
    }
}
