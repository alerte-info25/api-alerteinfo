<?php

namespace App\Models\EventsKeyWords;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventsKeyWordsModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'keywords',
        'slug',
    ];
}
