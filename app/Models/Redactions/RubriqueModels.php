<?php

namespace App\Models\Redactions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RubriqueModels extends Model
{
    use HasFactory;

    protected $fileable = ['rubrique','slug'];
}
