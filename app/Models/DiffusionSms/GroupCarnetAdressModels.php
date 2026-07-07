<?php

namespace App\Models\DiffusionSms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupCarnetAdressModels extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'group_name',
        'list_contact',
        'status',
        'slug'
    ];
}
