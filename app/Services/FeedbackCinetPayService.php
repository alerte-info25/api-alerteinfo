<?php


namespace App\Services;

use App\Models\Licence;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class FeedbackCinetPayService

{

    public static function update_licence_info($request,$num_licence)
    {
        //return $request->licence_id;
        $update_licence = Licence::where('id',$request->licence_id)
        ->update(['num_licence' => $num_licence,'payment' => 1]);
        return $update_licence;
        if($update_licence):
            
            
            $add_transaction = new Transaction();
            $add_transaction->transaction_id = $request->transaction_id;
            $add_transaction->montant = $request->montant;
            $add_transaction->author = $request->author;
            $add_transaction->author_phone = $request->author_phone;
            $add_transaction->date_transaction = $request->payment_date;
            $add_transaction->reference_licence = $request->licence_id;
            $add_transaction->status = "payé";


            return $add_transaction;
            $add_transaction->save();

            return true;
        endif;
            
    }
}