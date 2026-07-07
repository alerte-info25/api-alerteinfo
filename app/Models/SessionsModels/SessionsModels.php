<?php

namespace App\Models\SessionsModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionsModels extends Model
{
    use HasFactory;
    protected $fillable = [
        'date_debut',
        'date_fin',
        'description'
    ];
}
