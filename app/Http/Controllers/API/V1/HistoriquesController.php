<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\HistoriqueServices;
use Illuminate\Http\Request;

class HistoriquesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function get()
    {
        $all_historiques = HistoriqueServices::get();
        return $all_historiques;

    }
}
