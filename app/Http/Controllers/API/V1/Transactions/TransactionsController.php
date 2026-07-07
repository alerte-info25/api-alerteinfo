<?php

namespace App\Http\Controllers\API\V1\Transactions;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }



    public function get_transactions_list()
    {

        try {
            return Transaction::all();
        } catch (\Throwable $e) {

            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );

        }
    }

    public function search_in_transactions($query)
    {
        try {
            return Transaction::where('operations', 'LIKE', '%' . $query . '%')->get();
        } catch (\Throwable $e) {

            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );

        }
    }

    public function check_transactions_is_payed_or_unpaid($query)
    {
        try {
            if($query == 'payed') {
                return DB::table('transactions_models')->where('status', 'ACCEPTED')->get();
            } elseif($query == 'unpaid') {
                return DB::table('transactions_models')->where('status', 'REFUSED')->get();
            }else {
                return DB::table('transactions_models')->get();
            }
        } catch (\Throwable $e) {

            return response()->json(
                [
                    'status' => 'error',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );

        }
    }
}
