<?php

namespace App\Http\Controllers\API\V1\Abonnements;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\SendAccountMailer;
use App\Services\CodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Models\Abonnements\AbonnesModels;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AbonnesMobileModels\AbonnesMobileModels;

class AbonnesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    

    public function ctrl_getAbonneMobile(Request $request)
    {
        try {
            // pagination variables
            $perpage = $request->input('perpage', 20);
            $page = $request->input('page', 1);

            $abonnes = DB::table('abonnes_mobile_models')
            ->orderByDesc('id')
            ->paginate($perpage, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'abonneData' => $abonnes->items(),
                'pagination' => [
                    'total' => $abonnes->total(),
                    'per_page' => $abonnes->perPage(),
                    'current_page' => $abonnes->currentPage(),
                    'last_page' => $abonnes->lastPage(),
                ],
            ], Response::HTTP_OK);
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


            $checkUser = DB::table('users')->where('email',$request->abonne_email)->first();

            if($checkUser != null):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! L'adresse email est déjà utilisée pour un autre abonné"
                    ]
                );
            endif;


            // Utilisation de transactions pour assurer la cohérence des données
            DB::beginTransaction();

            $my_password = CodeGenerator::generatePassword();

            $store_user = new User();
            $store_user->email = $request->abonne_email;
            $store_user->password = password_hash($my_password, PASSWORD_BCRYPT);
            $store_user->user_type = "abonne";
            $store_user->phone = $request->abonne_phone_number;
            if (!$store_user->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => "Erreur! Échec de la création de l'utilisateur."
                ], 500);
            }

            // Création de l'abonné
            $store_abonne = new AbonnesMobileModels();
            $store_abonne->abonne_fname = $request->abonne_name;
            $store_abonne->abonne_lname = $request->abonne_name;
            $store_abonne->user_id = $store_user->id;
            $store_abonne->type_abonne = "premium";
            $store_abonne->abonne_phone_number = $request->abonne_phone_number;
            $store_abonne->abonne_email = $request->abonne_email;
            $store_abonne->slug = CodeGenerator::generateRfk();

            if (!$store_abonne->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => "Erreur! Échec de la création de l'abonné."
                ], 500);
            }

            // Commit de la transaction si tout est bon
            DB::commit();

            // Envoi de l'email de confirmation
            try {
                Mail::to($request->email)->send(new SendAccountMailer(
                    $request->password,
                    $store_abonne->abonne_fname ,
                    $store_abonne->abonne_email,
                    "ALERTE INFO: NOTIFICATION APRES CREATION DE COMPTE",
                    "L'agence de presse vous remercie pour la création de votre compte. Voici vos identifiants pour avoir accès à votre espace."
                ));
            } catch (Exception $e) {
                Log::error("Erreur lors de l'envoi de l'email de confirmation: " . $e->getMessage());
            }
            return response()->json(
                [
                    'status' => 'success',
                    'code' => 200,
                    'abonne_id' => $store_abonne->id,
                    'message' => "Ok ! Le compte a été créé avec succès. L'abonné va recevoir ses accès par mail."
                ], 200
            );

        } catch (\Throwable $e) {
            // En cas d'erreur, on annule la transaction
            DB::rollBack();
            Log::error("Erreur lors de la création de l'abonné: " . $e->getMessage());
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => "Erreur lors de la création de l'abonné: ",
                ], 500
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
                return  AbonnesModels::join('users', 'administration_models.user_id', '=', 'users.id')
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

            $current_account =  AbonnesModels::where('slug', $slug)->first();
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

                $current_account->abonne_name = $request->abonne_name;
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
                        'message' => "Oupps!, Aucun élément trouvé"
                    ]
                );
            else :
                $current_account =  AbonnesModels::where('slug', $slug)->first();

                User::where('id', $current_account->user_id)->delete();


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
