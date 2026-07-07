<?php

namespace App\Models\Engagements;

use Illuminate\Database\Eloquent\Model;
use App\Models\Epreuve\EpreuveModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EngagementInternationalDataModel extends Model
{
    use SoftDeletes;
    protected $table = 'engagement_international_data_models';
    protected $fillable = [
        'uuid',
        'engagement_code_unique',
        'pays',
        'genre',
        'epreuve_code_unique',
        'full_name',
        'date_naissance',
        'performance',
        'rang',
        'points',
    ];

    public function engagement()
    {
        return $this->belongsTo(EngagementInternationalModel::class,'engagement_code_unique','engagement_code_unique');
    }

    public function epreuve()
    {
        return $this->belongsTo(EpreuveModel::class,'epreuve_code_unique','epreuve_code_unique');
    }


    public function createData(array $data)
    {
        return $this->create([
            'uuid' => $data['uuid'],
            'engagement_code_unique' => $data['engagement_code_unique'],
            'pays' => $data['pays'],
            'genre' => $data['genre'],
            'epreuve_code_unique' => $data['epreuve_code_unique'],
            'full_name' => $data['full_name'],
            'date_naissance' => $data['date_naissance'],
            'performance' => $data['performance'],
        ]);
    }

    public function updateEngagementData(array $data, $uuid)
    {
        return $this->where('uuid', $uuid)->update([
            'pays' => $data['pays'],
            'genre' => $data['genre'],
            'epreuve_code_unique' => $data['epreuve_code_unique'],
            'full_name' => $data['full_name'],
            'date_naissance' => $data['date_naissance'],
            'performance' => $data['performance'],
        ]);
    }
}
