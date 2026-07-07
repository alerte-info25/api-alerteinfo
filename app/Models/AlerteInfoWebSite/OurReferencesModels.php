<?php

namespace App\Models\AlerteInfoWebSite;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OurReferencesModels extends Model
{
    use HasFactory;


    protected $fillable = [
        'contents',
        'slug'
    ];
}
