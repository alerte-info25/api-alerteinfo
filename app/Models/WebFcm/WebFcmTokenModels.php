<?php

namespace App\Models\WebFcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebFcmTokenModels extends Model
{
    use HasFactory;


    protected $fillable = ['tokens'];

}
