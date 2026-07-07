<?php

namespace App\Models\DiffusionSms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GestionOperatorModels extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'operator_name',
        'country',
        'status',
        'slug'
    ];
}
