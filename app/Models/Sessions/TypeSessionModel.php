<?php

namespace App\Models\Sessions;

use Illuminate\Database\Eloquent\Model;

class TypeSessionModel extends Model
{
    protected $table = 'type_session_models';
    protected $fillable = [
        'id',
        'type_session_code_unique',
        'type_session',
        'slug',
    ];
}
