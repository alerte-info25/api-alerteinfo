<?php

namespace App\Models\AbonnesWebModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriesAbonnesWebModels extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_code',
        'categorie',
        'can_copy',
        'can_share',
        'can_read',
        'can_download',
        'slug'
    ];
}
