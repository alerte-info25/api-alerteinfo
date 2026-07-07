<?php

namespace App\Http\Controllers\API\V1\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordOTP;
use App\Mail\ForgetPasswordMailer;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTFactory;
use App\Models\AbonnesWebModels\AbonnesWebModels;

class FrontendAuthentificatorController extends Controller

{
    public function authentification(Request $request)
    {
        try {
            // validate email and password
            if(empty($request->email_address)) {
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "L'adresse email est obligatoire"
                ], 400);
            }
            if(! filter_var($request->email_address, FILTER_VALIDATE_EMAIL)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "L'adresse email n'est pas valide"
                ], 400);
            }
            if(empty($request->user_password)) {
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le mot de passe est obligatoire"
                ], 400);
            }

            // check user account
            $user = AbonnesWebModels::where('email', $request->email_address)->first();

            if(!$user) {
                // user account not found
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 404,
                    'message' => "Votre compte est introuvable!"
                ], 404);
            }

            // check if user password is correct
            if(!password_verify($request->user_password, $user->password)) {
                // password is incorrect
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Le mot de passe est incorrect!"
                ], 400);
            }

            $credentials = [
                'email' => $request->email_address,
                'password' => $request->user_password
            ];
            
            $customTTL = 30*24*60*60;

            $__access_token = auth('abonne')->setTTL($customTTL)->attempt($credentials);

            
            if (!$__access_token) {
                Log::warning('Échec de la connexion', ['email' => $request->email_address]);
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Échec de la connexion"
                ], 401);
            }

            // update connected and last_login_at
            $user->connected = 1;
            $user->last_login_at = Carbon::now()->format('Y-m-d H:i:s');
            $user->save();

            // Log de la connexion réussie
            Log::info('Connexion réussie', ['email' => $request->email_address]);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Authentification réussie',
                'access_token' => $__access_token,
                'userData' => [
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'telephone' => $user->phone,
                    'connected' => $user->connected,
                    'account_code_unique' => $user->account_code_unique,
                    'expired_at' => auth('abonne')->factory()->setTTL($customTTL)
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::error("Erreur authentication sur le site web: " . $e->getMessage());
            return response()->json([
                'status' => 'Erreur serveur',
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.',
                'code' => 500
            ], 500);
        }
    }


    //logout_alerteinfo_web
    public function logoutAlerteInfoWeb(Request $request)
    {
        try {
            if(!auth('abonne')->check()){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "Une erreur est survenue lors de la déconnexion."
                ]);
            }

            auth('abonne')->logout();

            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'message' => "Vous avez été déconnecté avec succès.",
            ]);
        } catch (\Throwable $th) {
            // log Error
            Log::error('Erreur lors de la déconnection', [$th->getMessage()]);
            return response()->json([
                'status' => 'Erreur serveur',
                'code' => 500,
                'message' => "Une erreur est survenue lors de la déconnexion."
            ]);
        }
    }




    



    // process update user account
    public function processUpdateUserAccountPassword(Request $request)
    {
        try {
            //code...
            // check user account
            $accountModel = AbonnesWebModels::where('email', $request->email_address)->first();

            if ($accountModel == null) {
                return [
                    'status' => "Erreur",
                    'code' => 404,
                    'message' => "Votre compte est introuvable!"
                ];
            } else {
                $lenght = 6;
                $keys = substr(str_shuffle(
                    str_repeat($x = '1234567890', ceil($lenght / strlen($x)))
                ), 3, $lenght);


                $two_factor_secret = $keys;
                $current_date = Carbon::now()->addMinutes(5);
                $two_factor_code_sent_at = $current_date->format('Y-m-d H:i:s');

                $is_updated = $accountModel->update([
                    'two_factor_secret' => $two_factor_secret,
                    'two_factor_code_sent_at' => $two_factor_code_sent_at,
                ]);

                if ($is_updated) {
                    Mail::to($request->email_address)->send(new ResetPasswordOTP($two_factor_secret));
                    return response()->json([
                        'status' => 'succès',
                        'code' => 200,
                        'message' => 'Un code OTP a été envoyé à votre adresse e-mail.',
                    ]);
                } else {
                    return response()->json([
                        'status' => 'Erreur',
                        'code' => 500,
                        'message' => 'Une erreur est survenue lors de la mise à jour de votre mot de passe.',
                    ]);
                }
            }
        } catch (\Throwable $th) {
            Log::error('Erreur lors du traitement de la mise à jour du mot de passe', ['email' => $request->email_address, 'erreur' => $th->getMessage()]);
            return response()->json([
                'status' => 'Erreur serveur',
                'code' => 500,
                'message' => 'Erreur lors du traitement de la mise à jour du mot de passe'
            ], 500);
        }
    }

    // verify user account OTP
    public function verifyUserAccountOTP(Request $request)
    {
        try {
            //code...
            $accountModel = AbonnesWebModels::where('email', $request->email_address)->first();

            if ($accountModel == null) {
                return [
                    'status' => "Erreur",
                    'code' => 404,
                    'message' => "Votre compte est introuvable!"
                ];
            } else {
                $current_date = Carbon::now();
                $two_factor_code_sent_at = Carbon::parse($accountModel->two_factor_code_sent_at);

                if ($two_factor_code_sent_at->copy()->addMinutes(5)->lessThan($current_date)) {
                    return response()->json([
                        'status' => 'Erreur',
                        'code' => 401,
                        'message' => 'Le code OTP a expiré. Veuillez ré-soumettre le code.',
                    ]);
                }

                if ($accountModel->two_factor_secret == $request->otp_code) {
                    Log::info('OTP vérifié ', ['OTP' => $accountModel->two_factor_secret]);
                    $accountModel->update(
                        [
                            'two_factor_secret' => null,
                            'two_factor_code_sent_at' => null,
                        ]
                    );
                    return response()->json(
                        [
                            'code' => 200,
                            'status' => 'Succès',
                            'message' => "Veuillez saisir le code OTP envoyé à votre adresse email!"
                        ]
                    );
                } else {
                    Log::info('OTP non-vérifié ', ['OTP' => $accountModel->two_factor_secret]);
                    return response()->json(
                        [
                            'code' => 302,
                            'status' => 'Erreur',
                            'message' => "OTP incorrect."
                        ]
                    );
                }
            }
        } catch (\Throwable $th) {
            //log error
            Log::error('Erreur lors de la vérification de l\'OTP', [
                'email' => $request->email_address,
                'error' => $th->getMessage()
            ]);
            return response()->json([
                'status' => 'Erreur serveur',
                'code' => 500,
                'message' => 'Erreur lors de la vérification de l\'OTP: '
            ], 500);
        }
    }

    // resent user otp
    public function resentUserAccountOTP(Request $request)
    {
        try {
            
            $accountModel = AbonnesWebModels::where('email', $request->email_address)->first();

            if ($accountModel == null) {
                return [
                    'status' => "Erreur",
                    'code' => 404,
                    'message' => "Votre compte est introuvable!"
                ];
            } else {
                $lenght = 6;
                $keys = substr(str_shuffle(
                    str_repeat($x = '1234567890', ceil($lenght / strlen($x)))
                ), 3, $lenght);


                $two_factor_secret = $keys;
                $current_date = Carbon::now()->addMinutes(5);
                $two_factor_code_sent_at = $current_date->format('Y-m-d H:i:s');

                $is_updated = $accountModel->update([
                    'two_factor_secret' => $two_factor_secret,
                    'two_factor_code_sent_at' => $two_factor_code_sent_at,
                ]);

                if ($is_updated) {
                    Mail::to($request->email_address)->send(new ResetPasswordOTP($two_factor_secret));
                    return response()->json([
                        'status' => 'succès',
                        'code' => 200,
                        'message' => 'Un code OTP a été envoyé à votre adresse e-mail.',
                    ]);
                } else {
                    return response()->json([
                        'status' => 'Erreur',
                        'code' => 500,
                        'message' => 'Une erreur est survenue lors de la mise à jour de votre mot de passe.',
                    ]);
                }
            }
        } catch (\Throwable $th) {
            // Log the error message
            Log::error('Erreur lors de la réinitialisation du code OTP', ['exception' => $th->getMessage()]);
            return response()->json([
                'status' => 'Erreur serveur',
                'message' => 'Erreur lors de la réinitialisation du code OTP'
            ], 500);
        }
    }

    // update user account password
    public static function updateUserAccountPassword(Request $request)
    {
        try {

            if(empty($request->user_password)) {
                return response()->json([
                    'code' => 400,
                    'status' => 'Erreur',
                    'message' => 'Veuillez renseigner le nouveau mot de passe.'
                ], 400);
            }
            if(empty($request->email_address)) {
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "L'adresse email est obligatoire"
                ], 400);
            }
            if(! filter_var($request->email_address, FILTER_VALIDATE_EMAIL)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "L'adresse email n'est pas valide"
                ], 400);
            }

            $accountModel = AbonnesWebModels::where('email', $request->email_address)->first();

            if ($accountModel == null) {
                return response()->json([
                    'status' => "Erreur",
                    'code' => 404,
                    'message' => "Votre compte est introuvable!"
                ]);
            } else {
                $newPassword = $request->user_password;

                $is_updated = $accountModel->update(
                    [
                        'password' => password_hash($newPassword, PASSWORD_BCRYPT),
                    ]
                );

                if ($is_updated) {
                    $notifiction = "<p>Votre mot de passe a été modifié avec succès.</p>"
                    . "<p><strong>Adresse email:</strong> " . $request->email_address . "</p>"
                    . "<p><strong style='font-size: 1.3rem; color: red;'>Nouveau mot de passe:</strong> " . $newPassword . "</p>";


                    Mail::to($request->email_address)
                        ->send(new ForgetPasswordMailer($notifiction));

                    return response()->json(
                        [
                            'code' => 200,
                            'status' => 'succès',
                            'message' => "Ok ! Votre mot de passe a été modifié avec succès. Un mail vous a été envoyé sur votre adresse."
                        ]
                    );
                } else {
                    return response()->json([
                        'code' => 500,
                        'status' => 'Erreur',
                        'message' => 'Une erreur est survenue lors de la mise à jour de votre mot de passe.'
                    ]);
                }
            }
        } catch (\Throwable $th) {
            Log::error('Erreur lors de la mise à jour du mot de passe', ['exception' => $th->getMessage()]);
            return response()->json([
                'status' => 'Erreur serveur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la mise à jour du mot de passe'
            ], 500);
        }
    }



    // check abonne account
    public static function checkAbonneAccount(Request $request)
    {
        try {
            
            if(empty($request->email_address)) {
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "L'adresse email est obligatoire"
                ], 400);
            }
            if(! filter_var($request->email_address, FILTER_VALIDATE_EMAIL)){
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => "L'adresse email n'est pas valide"
                ], 400);
            }


            $accountModel = AbonnesWebModels::where('email', $request->email_address)->first();

            if ($accountModel == null) {
                return response()->json([
                    'code' => 404,
                    'status' => 'Erreur',
                    'message' => "Votre compte est introuvable!"
                ], 404);
            } else {
                return response()->json([
                    'code' => 200,
                    'status' => 'Succès',
                    'slug' => $accountModel->slug,
                    'message' => "Votre compte est actif. Vous pouvez continuer..."
                ], 200);
            }
        } catch (\Throwable $th) {
            //log error
            Log::error('Erreur lors de la vérification du compte', ['exception' => $th->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'Erreur serveur',
                'message' => 'Une erreur est survenue lors de la vérification du compte'
            ], 500);
        }
    }





    // ********************************** PRIVATE FUNCTIONS ********************************

    // check if user subscriptions exist
    private function checkIfUserSubscribed($account_code_unique)
    {
        $checkAbonnement = DB::table('abonnement_web_models')
        ->where('account_code_unique', $account_code_unique)
        ->get();
        if($checkAbonnement->count() > 0){
            return true;
        }else{
            return false;
        }
    }

    // check if user subscriptions is paid
    private function checkIfUserSubscribedIsPaid($account_code_unique)
    {
        $checkAbonnementIsPaid = DB::table('abonnement_web_models')
        ->where('account_code_unique', $account_code_unique)
        ->where('payments', 1)
        ->get();

        if($checkAbonnementIsPaid->count() > 0){
            return true;
        } else{
            return false;
        }
    }

    // check if user subscriptions is expired
    private function checkIfUserSubscribedIsExpired($account_code_unique)
    {
        $checkAbonnementIsExpired =  DB::table('abonnement_web_models')
        ->where('account_code_unique', $account_code_unique)
        ->whereDate('end_date', '>=', Carbon::now()->format('Y-m-d H:i:s'))
        ->get();
        if($checkAbonnementIsExpired->count() > 0){
            return true;
        } else{
            return false;
        }
    }


    // ******************************************* END PRIVATE FUNCTIONS ****************************************

}
