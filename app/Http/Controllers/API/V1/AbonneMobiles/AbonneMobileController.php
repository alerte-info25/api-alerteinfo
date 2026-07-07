<?php

namespace App\Http\Controllers\API\V1\AbonneMobiles;

use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\SendAccountMailer;
use App\Services\CodeGenerator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Models\AbonnesMobileModels\AbonnesMobileModels;

class AbonneMobileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }


    public function index()
    {
        try {
            return  DB::table('abonnes_mobile_models')->orderByDesc('id')->get();

        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
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
            if(empty($request->nom)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le nom est obligatoire"
                    ]
                );
            endif;
            if(empty($request->prenom)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le prénom est obligatoire"
                    ]
                );
            endif;

            if(empty($request->contact)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le numéro de téléphone est obligatoire"
                    ]
                );
            endif;

            if(empty($request->email)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! L'adresse email est obligatoire"
                    ]
                );
            endif;

            // check if email is valid
            if(!filter_var($request->abonne_email, FILTER_VALIDATE_EMAIL)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! L'adresse email n'est pas valide"
                    ]
                );
            endif;

            // check if email already exists in database
            $checkUser = DB::table('users')->where('email',$request->email)->first();

            if($checkUser != null):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! L'adresse email est déjà utilisée pour un autre abonné"
                    ]
                );
            endif;

            // check if password is conform to password confirmation
            if($request->password != $request->password_confirmation):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Les mots de passe ne correspondent pas"
                    ]
                );
            endif;

            // generate a random password
            //$my_password = Str::random(10);

            // hash the password
            $hashed_password = password_hash($request->password, PASSWORD_BCRYPT);

            $store_user = new User();
            $store_user->email = $request->email;
            $store_user->phone = $request->phone;
            $store_user->user_device = $request->device_info;
            $store_user->user_type = "abonne";
            $store_user->password = $hashed_password;

            if($store_user->save()):

                $store_abonne = new AbonnesMobileModels();
                $store_abonne->abonne_fname = $request->nom;
                $store_abonne->abonne_lname = $request->prenom;
                $store_abonne->user_id = $store_user->id;
                $store_abonne->type_abonne = $request->type_abonne;
                $store_abonne->abonne_phone_number = $request->contact;
                $store_abonne->abonne_email = $request->email;

                $store_abonne->slug = CodeGenerator::generateRfk();

                if($store_abonne->save()):

                    $subject_email = "ALERTE INFO: NOTIFICATION APRES CREATION DE COMPTE";
                    $default_text = "L'agence de presse vous remercie pour votre abonnement. Voici vos identifiants pour avoir accès votre espace.";

                    Mail::to($request->abonne_email)->send(new SendAccountMailer(
                        $request->password,
                        $store_abonne->abonne_fname . ' ' . $store_abonne->abonne_lname,
                        $store_abonne->abonne_email,
                        $subject_email,
                        $default_text
                    ));

                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'account_id' => $store_abonne->id,
                            'message' => "Ok ! Le compte a été créé avec succès. Vous allez recevoir vos accès par mail."
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
                return  AbonnesMobileModels::join('users', 'administration_models.user_id', '=', 'users.id')
                ->join('roles', 'administration_models.role_id', '=', 'roles.id')
                ->select('users.email', 'users.connected', 'roles.role', 'administration_models.*')
                ->where('administration_models.slug', $slug)
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

            if(empty($request->abonne_name)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le nom est obligatoire"
                    ]
                );
            endif;

            if(empty($request->abonne_phone_number)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le numéro est obligatoire"
                    ]
                );
            endif;

            if(empty($request->abonne_email)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le rôle est obligatoire"
                    ]
                );
            endif;

            $current_account =  AbonnesMobileModels::where('slug', $slug)->first();
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

            $update_user->email = $request->abonne_email;

            if($update_user->save()) :

                $current_account->abonne_fname = $request->nom;
                $current_account->abonne_lname = $request->prenom;
                $current_account->type_abonne = $request->type_abonne;
                $current_account->abonne_phone_number = $request->abonne_phone_number;
                $current_account->abonne_email = $request->abonne_email;

                if($current_account->save()):
                    return response()->json(
                        [
                            'status' => 'success',
                            'code' => 200,
                            'message' => "Ok!, L'abonné de a été modifié avec succès."
                        ]
                    );
                endif;
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification de l'abonné, veuillez réessayer!"
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
                        'message' => "Oupps ! Aucun élément trouvé"
                    ]
                );
            else :
                $current_account =  AbonnesMobileModels::where('slug', $slug)->first();

                User::where('id', $current_account->user_id)->delete();

                AbonnesMobileModels::where('slug', $slug)->delete();


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
}
