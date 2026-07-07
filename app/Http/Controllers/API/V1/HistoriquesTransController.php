<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Historiques;
use App\Models\HistoriqueTransaction;
use Illuminate\Http\Request;

class HistoriquesTransController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    
    public function get()
    {
        $all_historiques = HistoriqueTransaction::all();

        return $all_historiques;
    }
}
