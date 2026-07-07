<?php

namespace App\Models\Engagements;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use App\Models\Competition\CompetitionModel;
use App\Models\Competition\CategorieCompetitionModel;
use App\Models\Engagements\EngagementInternationalDataModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EngagementInternationalModel extends Model
{
    use SoftDeletes;
    protected $table = 'engagement_international_models';
    protected $fillable = [
        'uuid',
        'engagement_code_unique',
        'competition_code_unique',
        'cat_competition_code_unique',
    ];

    public function competition()
    {
        return $this->belongsTo(CompetitionModel::class,'competition_code_unique','competition_code_unique');
    }

    public function cat_competition()
    {
        return $this->belongsTo(CategorieCompetitionModel::class,'cat_competition_code_unique','categorie_code_unique');
    }

    public function engagementData()
    {
        return $this->hasMany(EngagementInternationalDataModel::class,'engagement_code_unique','engagement_code_unique');
    }

    public function createInitialData(array $data)
    {
        return $this->create([
            'uuid' => Str::uuid(),
            'engagement_code_unique' => $data['engagement_code_unique'],
            'competition_code_unique' => $data['competition_code_unique'],
            'cat_competition_code_unique' => $data['cat_competition_code_unique'],
        ]);
    }


}
