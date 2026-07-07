<?php

namespace App\Http\Controllers\API\V1\UsersAccounts;

use App\Http\Controllers\Controller;
use App\Mail\SendAccountMailer;
use App\Models\User;
use App\Models\UsersAccounts\AdministrationModels;
use App\Services\CodeGenerator;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AdministrationController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }




    public $administration_photo;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return  AdministrationModels::join('users', 'administration_models.user_id', '=', 'users.id')
            ->join('roles', 'administration_models.role_id', '=', 'roles.id')
            ->select('users.email', 'users.connected','users.user_type', 'roles.role', 'administration_models.*')
            ->get();

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


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            if(empty($request->first_name)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le prénom est obligatoire"
                    ]
                );
            endif;

            if(empty($request->last_name)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le nom est obligatoire"
                    ]
                );
            endif;


            

            if(empty($request->role_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le rôle est obligatoire"
                    ]
                );
            endif;


            $checkUser = DB::table('users')->where('email',$request->email)->where('user_type',$request->user_type)->first();

            if($checkUser != null):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! L'adresse email est déjà utilisée pour un autre compte"
                    ]
                );
            endif;

            $this->administration_photo = UploadService::upload_admin_photo($request);

            if($this->administration_photo == "error"):
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "L'enregistrement de la photo a échoué."
                    ]
                );
            endif;


            $my_password = CodeGenerator::generatePassword();

            $store_user = new User();
            $store_user->email = $request->email;
            $store_user->user_type = $request->type_accounts;
            $store_user->password = password_hash($my_password, PASSWORD_DEFAULT);

            if($store_user->save()):

                $store_administration_compte = new AdministrationModels();

                $store_administration_compte->first_name = $request->first_name;
                $store_administration_compte->last_name = $request->last_name;
                $store_administration_compte->user_id = $store_user->id;
                $store_administration_compte->role_id = $request->role_id;

                if($this->administration_photo != "file_not_found"):
                    $store_administration_compte->photo = $this->administration_photo;
                endif;

                $store_administration_compte->slug = CodeGenerator::generateRfk();

                if($store_administration_compte->save()):

                    $subject_email = "ALERTE INFO: NOTIFICATION APRES CREATION DE COMPTE";
                    $default_text = "
                        <p>
                            L'agence de presse vous remercie pour la création de votre compte.<br>
                            Voici vos identifiants pour accéder à votre espace :
                        </p>
                        <p>
                            🔗 <a href='https://admin-redaction.alerte-info.net' target='_blank' style='color:#1c5a41; text-decoration:none; font-weight:bold;'>
                                Accéder à votre espace personnel
                            </a>
                        </p>
                        
                        "
                    ;

                    Mail::to($request->email)->send(new SendAccountMailer(
                        $my_password,
                        $store_administration_compte->last_name . ' ' . $store_administration_compte->first_name,
                        $request->email,
                        $subject_email,
                        $default_text
                    ));

                    
                    return response()->json(
                        [
                            'status' => 'success',
                            'code' => 200,
                            'message' => "Ok ! Le compte  a été créé avec succès. L'utilisateur va recevoir ses accès par mail."
                        ]
                    );
                else:

                    User::where('id', $store_user->id)->delete();

                    return response()->json(
                        [
                            'status' => 'error',
                            'code' => 300,
                            'message' => "Erreur ! Échec de la création du compte, veuillez réessayer!"
                        ]
                    );
                endif;

            else:
                User::where('id', $store_user->id)->delete();
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la création du compte , veuillez réessayer!"
                    ]
                );
            endif;


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

    

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        try {
            if (!$slug) :
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Oupps!, Aucun élément trouvé"
                    ]
                );
            else :
                return  AdministrationModels::join('users', 'administration_models.user_id', '=', 'users.id')
                ->join('roles', 'administration_models.role_id', '=', 'roles.id')
                ->select('users.email', 'users.connected', 'roles.role', 'administration_models.*')
                ->get();

            endif;
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        try {
            if(empty($request->first_name)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur ! Le prénom est obligatoire"
                    ]
                );
            endif;

            if(empty($request->last_name)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le nom est obligatoire"
                    ]
                );
            endif;


            if(empty($request->role_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur ! Le rôle est obligatoire"
                    ]
                );
            endif;

            $current_account =  AdministrationModels::where('slug', $slug)->first();
            if($current_account == null):
                return response()->json(
                    [
                        'code' => "404",
                        'message' => "Le slug de l'utilisateur est introuvalbe."
                    ]
                );
            endif;
            //return $current_account;
            $update_user =  User::where('id', $current_account->user_id)->first();

            $update_user->email = $request->email;
            $update_user->user_type = $request->type_accounts;
            if($update_user->save()) :

                $this->administration_photo = UploadService::upload_admin_photo($request);

                if($this->administration_photo == "error"):
                    return response()->json(
                        [
                            'code' => "302",
                            'message' => "L'enregistrement de la photo a échoué."
                        ]
                    );
                endif;


                $current_account->first_name = $request->first_name;
                $current_account->last_name = $request->last_name;
                if($this->administration_photo != "file_not_found"):
                    $current_account->photo = $this->administration_photo;
                endif;
                $current_account->role_id = $request->role_id;

                if($current_account->save()):
                    return response()->json(
                        [
                            'status' => 'success',
                            'code' => 200,
                            'message' => "Ok!, Le compte de a été modifié avec succès."
                        ]
                    );
                endif;
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification du compte, veuillez réessayer!"
                    ]
                );
            endif;

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $slug)
    {
        try {
            if (!$slug) :
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Oupps!, Aucun élément trouvé"
                    ]
                );
            else :
                $current_account =  AdministrationModels::where('slug', $slug)->first();

                User::where('id', $current_account->user_id)->delete();
                AdministrationModels::where('slug', $slug)->delete();


                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => "Ok!,Suppression effectuée"
                    ]
                );

            endif;
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


    

    public function enable_or_disable_account(string $slug)
    {
        try {
            if (!$slug) :
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Oupps!, Aucun élément trouvé"
                    ]
                );
            else :
                $status = AdministrationModels::where('slug', $slug)->value('status');
                if($status == 1):
                    AdministrationModels::where('slug', $slug)->update(['status' => 0]);
                    return response()->json(
                        [
                            'status' => 'success',
                            'code' => 200,
                            'message' => "Ok!,Compte désactivé avec succès"
                        ]
                    );
                else:
                    AdministrationModels::where('slug', $slug)->update(['status' => 1]);
                    return response()->json(
                        [
                            'status' => 'success',
                            'code' => 200,
                            'message' => "Ok!,Compte activé avec succès"
                        ]
                    );
                endif;
            endif;
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


    public function update_administration_photo(Request $request)
    {
        try {

            $administration_account = AdministrationModels::where('slug', $request->slug)
            ->first();

            if($administration_account != null):
                $this->administration_photo = UploadService::upload_admin_photo($request);
                if($this->administration_photo == "error"):
                    return response()->json(
                        [
                            'code' => "302",
                            'message' => "L'enregistrement de la photo  a échoué."
                        ]
                    );
                endif;

                if($this->administration_photo != "file_not_found"):
                    $administration_account->photo = $this->administration_photo;
                endif;

                if($administration_account->save()):

                    $users_is_logged = DB::table('administration_models')
                    ->join('users', 'administration_models.user_id', '=', 'users.id')
                    ->join('roles', 'administration_models.role_id', '=', 'roles.id')
                    ->select('users.email', 'users.connected','roles.role', 'administration_models.*')
                    ->where('administration_models.slug', $request->slug)
                    ->where('administration_models.status', 1)
                    ->first();
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'user_data' => $users_is_logged,
                            'message' => "Ok!, La photo a été modifiée avec succès."
                        ]
                    );
                else:
                    return response()->json(
                        [
                            'status' => 'error',
                            'code' => 300,
                            'message' => "Oupps!, Échec de la modification de la photo, veuillez réessayer!"
                        ]
                    );
                endif;
            endif;


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
}
