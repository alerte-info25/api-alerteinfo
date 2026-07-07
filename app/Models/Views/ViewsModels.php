<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewsModels extends Model
{
    use HasFactory;

    protected $fileable = [
        'ip_address',
        'news_slug',
    ];
}
