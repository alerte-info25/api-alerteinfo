<?php

namespace App\Models\Quoideneufs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RubriquesQuoideneufModels extends Model
{
    use HasFactory;

    protected $fileable = ['rubrique','slug'];
}
