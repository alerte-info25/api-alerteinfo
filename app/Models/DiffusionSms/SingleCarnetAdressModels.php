<?php

namespace App\Models\DiffusionSms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SingleCarnetAdressModels extends Model
{
    use HasFactory;

    protected $fillable = [
        'fname',
        'lname',
        'email',
        'contact',
        'status',
        'slug'
    ];
}
