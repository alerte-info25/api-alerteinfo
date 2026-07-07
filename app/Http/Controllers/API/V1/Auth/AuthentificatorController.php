<?php

namespace App\Http\Controllers\API\V1\Auth;

use Exception;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordOTP;
use Illuminate\Support\Carbon;
use App\Services\FlashMessages;
use App\Mail\ForgetPasswordMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthentificatorController extends Controller
{

    public function un_authorised()
    {
        return response()->json(
            [
                'code' => 401, // code for authorization error
                'status' => 'erreur',
                'message' => "Oups! accès interdit !👺. Le token n'est plus valable ou une connexion est nécessaire"
            ]
        );
    }




    public function admin_authentificator(Request $request)
    {
        try {


            if (empty($request->email)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "L'adresse email est obligatoire"
                    ]
                );
            endif;

            if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "L'adresse e-mail n'est pas valide"
                    ]
                );
            endif;


            if (empty($request->password)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le mot de passe est obligatoire"
                    ]
                );
            endif;


            $credentials = request(['email', 'password']);

            if (!$token = auth()->attempt($credentials)) {
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Oups! accès interdit !👺, Email ou mot de passe introuvable"
                    ]
                );
            }

            $users = DB::table('users')->where('email', $request->email)->first();

            if (!$users || !password_verify($request->password, $users->password)) {
                return response()->json(
                    [
                        'code' => 300,
                        'status' => 'erreur',
                        'message' => "Le mot de passe est incorrecte"
                    ]
                );
            }


            $users_is_logged = DB::table('administration_models')
                ->join('users', 'administration_models.user_id', '=', 'users.id')
                ->join('roles', 'administration_models.role_id', '=', 'roles.id')
                ->select('users.email', 'users.connected', 'users.user_type', 'roles.role', 'administration_models.*')
                ->where('administration_models.user_id', $users->id)->first();


            if ($users_is_logged):
                DB::table('users')->where('id', $users->id)->update(['connected' => 1]);

                return response()->json(
                    [
                        'code' => 200,
                        'token' => $token,
                        'users' => $users_is_logged,
                        'status' => "succès",
                        'token_type' => 'Bearer',
                        'expires_in' => auth()->factory()->getTTL() * 24 * 60,
                        'message' => 'Vous êtes connecté 💚!'
                    ]
                );
            endif;


        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }
    }


    public function authentificated_abonne(Request $request)
    {
        try {
            if (empty($request->email)) {
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "L'adresse email est obligatoire"
                ]);
            }

            if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "L'adresse e-mail n'est pas valide"
                ]);
            }

            if (empty($request->password)) {
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "Le mot de passe est obligatoire"
                ]);
            }

            $credentials = $request->only(['email', 'password']);

            if (!$token = auth()->attempt($credentials)) {
                return response()->json([
                    'code' => 302,
                    'status' => 'erreur',
                    'message' => "Oups! accès interdit !👺, Email ou mot de passe introuvable"
                ]);
            }

            $user = DB::table('users')->where('email', $request->email)->first();

            if (!$user || !password_verify($request->password, $user->password)) {
                return response()->json([
                    'code' => 300,
                    'status' => 'erreur',
                    'message' => "Le mot de passe est incorrecte"
                ]);
            }

            // Vérifier si l'utilisateur est déjà connecté sur un autre appareil
            if ($user->connected && $user->user_device !== $request->device_token) {
                // Envoyer une notification à l'appareil précédemment connecté pour le déconnecter
                $this->sendDisconnectNotification($user->user_device);
            }

            // Mettre à jour l'utilisateur pour utiliser le nouvel appareil
            DB::table('users')->where('id', $user->id)->update([
                'user_device' => $request->device_token,
                'connected' => 1
            ]);

            // get Abonné logged data
            $users_is_logged = DB::table('abonnes_mobile_models')
                ->join('users', 'abonnes_mobile_models.user_id', '=', 'users.id')
                ->select(
                    'users.email',
                    'users.connected',
                    'users.user_type',
                    'abonnes_mobile_models.abonne_fname as nom',
                    'abonnes_mobile_models.abonne_lname as prenom',
                    'abonnes_mobile_models.abonne_phone_number as contact',
                    'abonnes_mobile_models.type_abonne',
                    'abonnes_mobile_models.id  as account_id',
                    'abonnes_mobile_models.created_at',
                )
                ->where('abonnes_mobile_models.user_id', $user->id)->first();

            if ($users_is_logged) {
                return response()->json([
                    'code' => 200,
                    'token' => $token,
                    'users' => $users_is_logged,
                    'status' => "succès",
                    'token_type' => 'Bearer',
                    'expires_in' => auth()->factory()->getTTL(),
                    'message' => 'Vous êtes connecté 💚!'
                ]);
            }

            // Si aucun abonné n'est trouvé
            return response()->json([
                'code' => 404,
                'status' => 'erreur',
                'message' => "Aucun abonné trouvé pour cet utilisateur"
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    // Méthode pour envoyer une notification de déconnexion
    private function sendDisconnectNotification($oldDeviceToken)
    {
        // Logique pour envoyer une notification via OneSignal
        $data = [
            'app_id' => env('ONESIGNAL_APP_ID'),
            'include_player_ids' => [$oldDeviceToken],
            'contents' => ['en' => "Vous avez été déconnecté car votre compte a été utilisé sur un autre appareil !"],
            'small_icon' => 'ic_stat_icon_monochrome',
            'headings' => ['en' => "Déconnexion"],
            'data' => [
                'type' => 'disconnect',
                'action' => 'force_logout'
            ],
            // 'android_channel_id' => 'disconnect_channel', // Assurez-vous de créer ce canal dans votre application Android
            // 'priority' => 10, // Priorité élevée pour Android
            'content_available' => true, // Pour iOS, permet le traitement en arrière-plan
            'mutable_content' => true,
        ];

        // Envoyer la requête à OneSignal
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', $data);

        return $response->json();
    }

    public function checkUserAccount(Request $user)
    {

        try {
            if (empty($user->email)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "L'adresse e-mail est obligatoire."
                    ]
                );
            endif;

            if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "L'adresse e-mail n'est pas valide"
                    ]
                );
            endif;

            $is_admin_accounts = DB::table('administration_models')
                ->join('users', 'administration_models.user_id', '=', 'users.id')->where('users.email', $user->email)
                ->first();

            if ($is_admin_accounts == null) {
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Oups ! Votre compte n'existe pas ou est introuvable"
                    ]
                );
            } elseif ($is_admin_accounts != null) {
                return response()->json(
                    [
                        'code' => 200,
                        'status' => 'succès',
                        'message' => "Ok ! Vous pouvez continuer."
                    ]
                );
            }
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }




    }



    public function updateUserPassword(Request $user)
    {
        try {
            if (empty($user->email)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "L'adresse e-mail est obligatoire."
                    ]
                );
            endif;

            if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "L'adresse e-mail n'est pas valide"
                    ]
                );
            endif;

            if (empty($user->password)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le mot de passe est obligatoire."
                    ]
                );
            endif;

            $is_admin_accounts = DB::table('administration_models')
                ->join('users', 'administration_models.user_id', '=', 'users.id')->where('users.email', $user->email)
                ->first();



            if ($is_admin_accounts == null) {
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Oups ! Votre compte n'existe pas ou est introuvable"
                    ]
                );
            } elseif ($is_admin_accounts != null) {

                $password = password_hash($user->password, PASSWORD_BCRYPT);

                $isUpdated = DB::table('users')->where('email', $user->email)->update([
                    'password' => $password
                ]);
                if ($isUpdated) {

                    $notifiction = "Votre mot de passe a été modifié avec succès." . " " . "#Adresse email: " . " " . $user->email . " " . "#Nouveau mot de passe: " . " " . $user->password;
                    Mail::to($user->email)
                        ->send(new ForgetPasswordMailer($notifiction));

                    return response()->json(
                        [
                            'code' => 200,
                            'status' => 'succès',
                            'message' => "Ok ! Votre mot de passe a été modifié avec succès. Un mail vous a été envoyé sur votre adresse."
                        ]
                    );
                }
            }

        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 302,
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    public function logout()
    {
        try {
            $user = Auth::user();
            DB::table('users')->where('id', $user->id)->update(['connected' => 0, 'user_device' => null]);
            Session::flush();
            Auth::logout();

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => "Merci ! Vous vous êtes déconnecté"
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 302,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function checkSession(Request $request)
    {
        $user = $request->user();
        $deviceId = $request->input('device_id');

        $currentDevice = User::where('id', $user->id)
            ->where('user_device', $deviceId)
            ->first();

        if (!$currentDevice) {
            // L'utilisateur n'est plus connecté sur cet appareil
            return response()->json([
                'status' => 'disconnected',
                'code' => "401",
                'message' => 'Votre session a expiré ou vous avez été déconnecté.'
            ], 401);
        }

        return response()->json([
            'status' => 'connected',
            'message' => 'Session active'
        ], 200);
    }


    protected function guard()
    {
        return Auth::guard();
    }

    public function sendResetLinkEmailByMobile(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Un lien de réinitialisation a été envoyé à votre adresse e-mail.'
                ], 200);
            } else {
                $errorMessage = $this->getErrorMessage($status);
                return response()->json([
                    'status' => 'error',
                    'message' => $errorMessage
                ], 400);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation échouée',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.'
            ], 500);
        }
    }

    private function getErrorMessage($status)
    {
        switch ($status) {
            case Password::RESET_THROTTLED:
                return 'Veuillez attendre avant de réessayer.';
            case Password::INVALID_USER:
                return 'Nous ne pouvons pas trouver un utilisateur avec cette adresse e-mail.';
            default:
                return 'Impossible d\'envoyer le lien de réinitialisation. Veuillez réessayer.';
        }
    }

    public function sendOTP(Request $request)
    {
        Log::info("Début de la fonction sendOTP", ['email' => $request->email]);
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                Log::warning("Validation échouée pour sendOTP", ['errors' => $validator->errors()->toArray()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            Log::info("Recherche de l\'utilisateur", ['email' => $request->email]);
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                Log::warning("Utilisateur non trouvé", ['email' => $request->email]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Aucun utilisateur trouvé avec cette adresse e-mail.',
                ], 404);
            }

            Log::info("Utilisateur trouvé, génération de l\'OTP", ['user_id' => $user->id]);
            $otp = mt_rand(100000, 999999);
            $user->two_factor_secret = $otp;
            $user->two_factor_code_sent_at = Carbon::now();
            $user->save();

            Log::info("OTP généré et enregistré", ['user_id' => $user->id, 'otp' => $otp]);

            Log::info("Envoi de l\'email avec l\'OTP", ['user_id' => $user->id, 'email' => $user->email]);
            Mail::to($user->email)->send(new ResetPasswordOTP($otp));

            Log::info("Email envoyé avec succès", ['user_id' => $user->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Un code OTP a été envoyé à votre adresse e-mail.',
            ]);
        } catch (Exception $e) {
            Log::error("Erreur lors de l\'envoi de l\'OTP", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de l\'envoi de l\'OTP.',
            ], 500);
        }
    }

    public function verifyOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non trouvé.',
                ], 404);
            }

            if (
                $user->two_factor_secret != $request->otp ||
                Carbon::now()->diffInMinutes($user->two_factor_code_sent_at) > 15
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Code OTP invalide ou expiré.',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Code OTP vérifié avec succès.',
            ]);
        } catch (Exception $e) {
            Log::error('Erreur lors de la vérification de l\'OTP: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la vérification de l\'OTP.',
            ], 500);
        }
    }

    public function resetPasswordWithOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non trouvé.',
                ], 404);
            }

            if (
                $user->two_factor_secret != $request->otp ||
                Carbon::now()->diffInMinutes($user->two_factor_code_sent_at) > 15
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Code OTP invalide ou expiré.',
                ], 400);
            }

            // $user->password = Hash::make($request->password);
            $user->password = password_hash($request->password, PASSWORD_BCRYPT);
            $user->two_factor_secret = null;
            $user->two_factor_code_sent_at = null;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Mot de passe réinitialisé avec succès.',
            ]);
        } catch (Exception $e) {
            Log::error('Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la réinitialisation du mot de passe.',
            ], 500);
        }
    }

}

// two_factor_secret
// two_factor_code_sent_at
