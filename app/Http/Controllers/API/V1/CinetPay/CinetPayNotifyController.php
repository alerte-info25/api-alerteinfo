<?php

namespace App\Http\Controllers\API\V1\CinetPay;

use Exception;
use Illuminate\Http\Request;
use App\Services\CodeGenerator;
use Illuminate\Support\Facades\DB;
use App\Services\CinetPay\Marchand;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\WebTransactions\WebTransactionModels;

class CinetPayNotifyController extends Controller
{

    public function cinetNotify(Request $request)
    {
        if (isset($request->cpm_trans_id)) {

            try {

                $cinetpay_check = [
                    "apikey" => Marchand::get_apikey(),
                    "site_id" => $request->cpm_site_id,
                    "transaction_id" => $request->cpm_trans_id
                ];
                $notify_data = $this->check_payment_status($cinetpay_check);
                $data_decode = json_decode($notify_data, true);

                $object_data = (object) $data_decode;

                //return $object_data;
                //return $object_data;
                //$code = 627 // EHEC
                //$code = 00 // SUCCESS
                if ($object_data->code == '00') {

                    // check if the transaction is already in the database
                    $checkTrans = DB::table('web_transaction_models')->where('transaction_id', $request->cpm_trans_id)->first();
                    if($checkTrans != null && $checkTrans->status == 'PENDING') {
                        DB::table('web_transaction_models')->where('transaction_id', $request->cpm_trans_id)
                        ->update([
                            'montant' => $object_data->data['amount'],
                            'operations' => $object_data->data['description'],
                            'method_payment' => $object_data->data['payment_method'],
                            'date_transaction' => date('Y-m-d H:i:s', strtotime($object_data->data['payment_date'])),
                            'status' => $object_data->data['status'],
                        ]);

                        Log::info("Donnée de transaction mise à jour");
    
                        $is_abonnements = DB::table('abonnement_web_models')
                            ->where('abonnement_web_code', $request->cpm_trans_id)
                            ->first();
    
    
                        if ($is_abonnements != null) {
                            DB::table('abonnement_web_models')->where('abonnement_web_code', $request->cpm_trans_id)
                            ->update([
                                'updated_at' => date('Y-m-d H:i:s', strtotime(now())),
                                'payments' => 1,
                            ]);
    
                            Log::info("Mise à jour de la date de modification et le paiement");
                        }
    
                        $solde = DB::table('web_solde_models')->where('id', 1)->first();
    
    
                        DB::table('web_solde_models')->update([
                            'montants' => $solde->montants == 0
                                ? floatval($object_data->data['amount'])
                                : floatval($solde->montants) + floatval($object_data->data['amount']),
                            'montants_net' => $solde->montants == 0
                                ? floatval($object_data->data['amount']) - (floatval($object_data->data['amount']) * 0.03)
                                : floatval($solde->montants_net) + (floatval($object_data->data['amount']) - (floatval($object_data->data['amount']) * 0.03)),
                            'slug' => CodeGenerator::generateSlugCode()
                        ]);
    
                        Log::info("Mise à jour de la date du solde le paiement");
                    }

                    if($checkTrans == null){
                        $add_transaction = new WebTransactionModels();
                        $add_transaction->transaction_id = $request->cpm_trans_id;
                        $add_transaction->montant = $object_data->data['amount'];
                        $add_transaction->operations = $object_data->data['description'];
                        $add_transaction->method_payment = $object_data->data['payment_method'];
                        $add_transaction->date_transaction = date('Y-m-d H:i:s', strtotime($object_data->data['payment_date']));
                        $add_transaction->status = $object_data->data['status'];
                        $add_transaction->save();

                        Log::info("Donnée de transaction enregistrée");
    
                        $is_abonnements = DB::table('abonnement_web_models')
                            ->where('abonnement_web_code', $request->cpm_trans_id)
                            ->first();
    
    
                        if ($is_abonnements != null) {
                            DB::table('abonnement_web_models')->where('abonnement_web_code', $request->cpm_trans_id)
                            ->update([
                                'updated_at' => date('Y-m-d H:i:s', strtotime(now())),
                                'payments' => 1,
                            ]);
    
                            Log::info("Mise à jour de la date de modification et le paiement");
                        }
    
                        $solde = DB::table('web_solde_models')->where('id', 1)->first();
    
    
                        DB::table('web_solde_models')->update([
                            'montants' => $solde->montants == 0
                                ? floatval($object_data->data['amount'])
                                : floatval($solde->montants) + floatval($object_data->data['amount']),
                            'montants_net' => $solde->montants == 0
                                ? floatval($object_data->data['amount']) - (floatval($object_data->data['amount']) * 0.03)
                                : floatval($solde->montants_net) + (floatval($object_data->data['amount']) - (floatval($object_data->data['amount']) * 0.03)),
                            'slug' => CodeGenerator::generateSlugCode()
                        ]);
    
                        Log::info("Mise à jour de la date du solde le paiement");
                    }


                }
                

                if($object_data->code == '662') {
                    $add_transaction = new WebTransactionModels();
                    $add_transaction->transaction_id = $request->cpm_trans_id;
                    $add_transaction->montant = $object_data->data['amount'];
                    $add_transaction->operations = $object_data->data['description'] ?? 'En attente';
                    $add_transaction->method_payment = $object_data->data['payment_method'] ?? 'En attente';
                    $add_transaction->date_transaction =  date('Y-m-d H:i:s', strtotime(now())) ;
                    $add_transaction->status = $object_data->data['status'] ?? 'En attente';

                    $add_transaction->save();
                }

                if ($object_data->code == '627') {
                    $add_transaction = new WebTransactionModels();
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


    //public function

    public function check_payment_status($cinet_pay_config)
    {

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
        } else {
            $res = json_encode($response, true);
            return $response;
        }
    }
}
