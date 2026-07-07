<?php

namespace App\Http\Controllers\API\V1\Transactions;

use Exception;
use Illuminate\Http\Request;
use App\Services\CodeGenerator;
use Illuminate\Support\Facades\DB;
use App\Services\CinetPay\Marchand;
use App\Http\Controllers\Controller;
use App\Models\Transaction;

class NotifyController extends Controller
{
    public function notify(Request $request)
    {
        if (isset($request->cpm_trans_id)) {

            try {


                $cinetpay_check = [
                    "apikey" => Marchand::get_apikey(),
                    "site_id" => $request->cpm_site_id,
                    "transaction_id" => $request->cpm_trans_id
                ];
                $notify_data  = $this->check_payment_status($cinetpay_check);
                $data_decode = json_decode($notify_data, true) ;

                $object_data = (object) $data_decode;

                //$code = 627 // EHEC
                //$code = 00 // SUCCESS
                if($object_data->code   == '00'){

                    $add_transaction = new Transaction();
                    $add_transaction->transaction_id = $request->cpm_trans_id;
                    $add_transaction->montant = $object_data->data['amount'];
                    $add_transaction->operations = $object_data->data['description'];
                    $add_transaction->method_payment = $object_data->data['payment_method'];
                    $add_transaction->date_transaction = date('Y-m-d H:i:s', strtotime($object_data->data['payment_date']));
                    $add_transaction->status = $object_data->data['status'];
                    $add_transaction->save();

                    // check if the transaction is for coupons article

                    $is_coupons_article = DB::table('coupons_articles_models')->where('transaction_code', $request->cpm_trans_id)->first();
                    $is_coupons_article_papier = DB::table('coupons_article_papier_models')->where('transaction_code', $request->cpm_trans_id)->first();
                    $is_abonnements = DB::table('abonnements_models')->where('transaction_code', $request->cpm_trans_id)->first();


                    if ($is_coupons_article  != null) {
                        DB::table('coupons_articles_models')->where('transaction_code', $request->cpm_trans_id)->update([
                            'validited_at' => date('Y-m-d H:i:s', strtotime(now())),
                            'payments' => 1,
                        ]);
                    }

                    if ($is_coupons_article_papier != null) {
                        DB::table('coupons_article_papier_models')->where('transaction_code', $request->cpm_trans_id)->update([
                            'validited_at' => date('Y-m-d H:i:s', strtotime(now())),
                            'payments' => 1,
                        ]);
                    }

                    if ($is_abonnements != null) {
                        DB::table('abonnements_models')->where('transaction_code', $request->cpm_trans_id)->update([
                            'updated_at' => date('Y-m-d H:i:s', strtotime(now())),
                            'payments' => 1,
                        ]);
                    }

                    $solde = DB::table('solde_models')->first();


                    if ($solde->solde == 0) {
                        DB::table('solde_models')->update([
                            'solde_models' => (int) $object_data->data['amount'],
                            'slug' => CodeGenerator::generateSlugCode()
                        ]);
                    }else {
                        DB::table('solde_models')->update([
                            'solde' => (int) $solde->montants + (int) $object_data->data['amount'],
                            'slug' => CodeGenerator::generateSlugCode()
                        ]);
                    }
                }

                if ($object_data->code   == '627'){

                    $add_transaction = new Transaction();
                    $add_transaction->transaction_id = $request->cpm_trans_id;
                    $add_transaction->montant = $object_data->data['amount'];
                    $add_transaction->operations = $object_data->data['description'];
                    $add_transaction->method_payment = $object_data->data['payment_method'];
                    $add_transaction->date_transaction = date('Y-m-d H:i:s', strtotime($object_data->data['payment_date']));
                    $add_transaction->status = $object_data->data['status'];
                    $add_transaction->save();

                }

            } catch (Exception $e) {
                echo "Erreur :" . $e->getMessage();
            }
        } else {
            // direct acces on IPN
            echo "cpm_trans_id non fourni";
        }
    }


    public function check_payment_status($cinet_pay_config) {

        $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment/check',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($cinet_pay_config, true),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
            echo $err;
            //throw new Exception("Error :" . $err);
            }
            else{
            $res = json_encode($response , true);
            return $response;
        }
    }
}
