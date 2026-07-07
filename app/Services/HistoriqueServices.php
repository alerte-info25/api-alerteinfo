<?php

namespace App\Services;

use App\Models\HistoriqueManager;
use Illuminate\Support\Facades\DB;
use App\Models\HistoriqueTransaction;

class HistoriqueServices

{

    public static function get()
    {
        try {
            return DB::table('historique_managers')->orderBy('id','desc')->get();
        } catch (\Throwable $e) {
            return "bad_request";
        }

    }

    public static function manager($content,$author)
    {
        try {
            
            $add = new HistoriqueManager();
            $add->author = $author;
            $add->content = $content;
            if($add->save()):
                return "success";
            endif;
        } catch (\Throwable $e) {
            return "bad_request";
        }

    }


    public static function transaction(
        $transaction_id,$montant,$author,$author_phone,$date_transaction,$status)
    {
        $add = new HistoriqueTransaction(); //
        $add->transaction_id = $transaction_id;
        $add->montant = $montant;
        $add->author = $author;
        $add->author_phone = $author_phone;
        $add->date_transaction = $date_transaction;
        $add->status = $status;
        $add->save();
    }
}
