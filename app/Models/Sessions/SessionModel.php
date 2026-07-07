<?php

namespace App\Models\Sessions;

use Illuminate\Database\Eloquent\Model;

class SessionModel extends Model
{
    protected $table = 'session_models';
    protected $fillable = [
        'id',
        'session_code_unique',
        'type_session_code_unique',
        'session_started_at',
        'session_ended_at',
        'description',
        'slug',
    ];


    protected $casts = [
        'session_started_at' => 'date',
        'session_ended_at' => 'date',
    ];

    public function typeSession()
    {
        return $this->belongsTo(TypeSessionModel::class, 'type_session_code_unique', 'type_session_code_unique');
    }
}
