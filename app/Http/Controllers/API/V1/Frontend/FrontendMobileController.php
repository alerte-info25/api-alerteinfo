<?php

namespace App\Http\Controllers\API\V1\Frontend;

use Log;
use Http;
use DateTime;
use Exception;
use DateInterval;
use Notification;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserDevice;
use App\Mail\Notifications;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ProblemReport;
use App\Models\ContactMessage;
use App\Mail\SendAccountMailer;
use App\Services\CodeGenerator;
use App\Services\UploadService;
use App\Models\NotificationPush;
use App\Models\ProblemReportImage;
use Illuminate\Support\Facades\DB;
use App\Services\CinetPay\Marchand;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Redactions\DepecheModels;
use Illuminate\Support\Facades\Validator;
use App\Models\Redactions\CountriesModels;
use App\Models\Transactions\TransactionsModels;
use App\Models\AbonnesMobileModels\AbonnesMobileModels;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\AbonnementsMobileModels\AbonnementsMobileModels;
use App\Models\AbonnementsMobileModels\ForfaitsAbonnementsMobileModels;
use App\Services\NotificationPushService;
use App\Services\GeniusPay\GeniusMarchand;
class FrontendMobileController extends Controller
{
    public function get_mobile_recents_depeches_and_flashes(Request $request)
    {
        try {
            if ($request->customer_countries_id == 'undifined') {
                $depeche_data = DB::table('depeche_models')
                    ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                    ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                    ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'depeche_models.*')
                    ->orderBy('depeche_models.id', 'desc')
                    ->limit(15)
                    ->get();

                $flash_data = DB::table('flashes_models')
                    ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                    ->join('rubrique_models', 'flashes_models.rubrique_id', '=', 'rubrique_models.id')
                    ->select(
                        'countries_models.pays',
                        'countries_models.flag',
                        'rubrique_models.rubrique',
                        'flashes_models.*'
                    )
                    ->orderBy('flashes_models.id', 'desc')
                    ->limit(10)
                    ->get();
                return [
                    'depeche_data' => $depeche_data,
                    'flashes_data' => $flash_data,
                ];
            } else {

                $country_to_array = explode(',', $request->customer_countries_id);

                $depeche_data = DB::table('depeche_models')
                    ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                    ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                    ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'depeche_models.*')
                    ->whereIn('depeche_models.pays_id', $country_to_array)
                    ->orderBy('depeche_models.id', 'desc')
                    ->limit(15)
                    ->get();

                $flash_data = DB::table('flashes_models')
                    ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                    ->join('rubrique_models', 'flashes_models.rubrique_id', '=', 'rubrique_models.id')
                    ->select(
                        'countries_models.pays',
                        'countries_models.flag',
                        'rubrique_models.rubrique',
                        'flashes_models.*'
                    )
                    ->whereIn('flashes_models.pays_id', $country_to_array)
                    ->orderBy('flashes_models.id', 'desc')
                    ->limit(10)
                    ->get();
            }
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => $th->getMessage(),
                ]
            );
        }
    }


    // get full depeches join countries models and rubriques models and genre journalistiques models
    public function get_mobile_depeches()
    {
        // TODO: Implement fetching full dépêche from API and returning it

        try {
            return DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'genre_journalistique_models.genre',
                    'depeche_models.*'
                )
                ->orderBy('depeche_models.id', 'desc')
                ->limit(100)
                ->get();
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => $th->getMessage(),
                ]
            );
        }
    }

    public function search_mobile_depeches(Request $request)
    {
        // Récupérer les filtres depuis la requête
        $rubriqueId = $request->input('rubrique_id');
        $paysId = $request->input('pays_id');

        try {
            // Construire la requête de base
            $query = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'genre_journalistique_models.genre',
                    'depeche_models.*'
                )
                ->orderBy('depeche_models.id', 'desc')
                ->limit(100);

            // Appliquer les filtres conditionnellement
            if (!empty($rubriqueId)) {
                $query->where('depeche_models.rubrique_id', $rubriqueId);
            }

            if (!empty($paysId)) {
                $query->where('depeche_models.pays_id', $paysId);
            }

            // Récupérer les résultats
            $depeches = $query->get();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $depeches,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }



    // get depeche details from API by slug

    public function get_mobile_depeche_details_by_slug($slug, $paysId)
    {
        try {
            Log::info('Début de la méthode get_mobile_depeche_details_by_slug');

            // Récupérer les détails de la dépêche
            $depeche = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'depeche_models.*')
                ->where('depeche_models.slug', $slug)
                ->where('depeche_models.pays_id', $paysId)
                ->first();

            Log::info('Détails de la dépêche récupérés', ['depeche' => $depeche]);

            // Vérification si la dépêche est trouvée
            if (!$depeche) {
                Log::warning('Dépêche non trouvée', ['slug' => $slug]);
                return response()->json([
                    'status' => 'not-found',
                    'code' => 404,
                    'message' => 'Dépêche non trouvée',
                ], 404);
            }

            // Vérification de l'utilisateur connecté
            $user = Auth::user();
            if (!$user) {
                Log::warning('Utilisateur non connecté');
                return response()->json([
                    'status' => 'unauthentified',
                    'code' => 401,
                    'message' => 'Utilisateur non connecté. Veuillez vous connecter pour accéder aux détails de la dépêche.',
                ], 401);
            }

            // Vérification de l'abonné
            $abonne = AbonnesMobileModels::where('user_id', $user->id)->first();
            if (!$abonne) {
                Log::warning('Utilisateur non abonné', ['user_id' => $user->id]);
                return response()->json([
                    'status' => 'forfait-not-found',
                    'code' => 403,
                    'message' => 'Vous n\'avez aucun abonnement actif. Veuillez souscrire à un abonnement.',
                ], 403);
            }

            // Récupérer tous les abonnements de l'utilisateur
            $abonnements = AbonnementsMobileModels::where('abonne_id', $abonne->id)->where('payments', 1)
                ->with(['forfait', 'abonne'])
                ->get();

            if ($abonnements->isEmpty()) {
                Log::warning('Aucun abonnement trouvé pour l\'utilisateur', ['user_id' => $user->id]);
                return response()->json([
                    'status' => 'forfait-not-found',
                    'code' => 403,
                    'message' => 'Aucun abonnement trouvé. Veuillez souscrire à un abonnement pour accéder à cette dépêche.',
                ], 403);
            }

            // Vérifier si au moins un abonnement est valide
            $abonnementValide = null;

            foreach ($abonnements as $abonnement) {
                // Convertir les dates en objets Carbon
                $dateDebut = Carbon::parse($abonnement->date_debut);
                $dateFin = Carbon::parse($abonnement->date_fin);

                // Vérifier la validité de l'abonnement
                if (now()->between($dateDebut, $dateFin)) {
                    // Vérifier si le pays de la dépêche est couvert par l'abonnement
                    $pays_ids = explode(',', $abonnement->abonne_country_id);
                    if (in_array($depeche->pays_id, $pays_ids)) {
                        $abonnementValide = $abonnement;
                        break; // Sortir de la boucle car on a trouvé un abonnement valide
                    }
                }
            }

            // Si aucun abonnement valide n'est trouvé
            if (!$abonnementValide) {
                Log::warning('Aucun abonnement valide trouvé pour ce pays', ['user_id' => $user->id]);
                return response()->json([
                    'status' => 'unsubscribe',
                    'code' => 400,
                    'message' => 'Votre abonnement ne couvre pas le pays de cette dépêche. Veuillez souscrire à un abonnement pour ce pays.',
                ], 400);
            }

            // Toutes les conditions sont remplies, retourner les détails de la dépêche
            Log::info('Toutes les conditions sont remplies, retour des détails de la dépêche');
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $depeche,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Erreur lors du traitement : ' . $th->getMessage());

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des détails de la dépêche. Veuillez réessayer plus tard.',
            ], 500);
        }
    }


    // get full depeches by country
    public function get_full_depeche_by_country($country)
    {
        try {
            $country_to_array = implode(',', $country);

            return DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'depeche_models.*')
                ->whereIn('depeche_models.pays_id', $country_to_array)
                ->orderBy('depeche_models.id', 'desc')
                ->limit(100)
                ->get();
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => $th->getMessage(),
                ]
            );
        }
    }

    public function get_full_depeche_archives()
    {
        try {
            return DB::table('depeche_models')
                ->select(DB::raw('count(id) as `data`'), DB::raw("created_at as new_date"), DB::raw('YEAR(created_at) year, MONTH(created_at) month'))
                ->groupby('year', 'month')
                ->limit(12)
                ->orderByDesc('created_at')
                ->get();

        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    public function get_full_depeche_archives_data()
    {
        try {
            return DB::table('depeche_models')
                ->select(DB::raw('count(id) as `data`'), DB::raw("created_at as new_date"), DB::raw('YEAR(created_at) year, MONTH(created_at) month'))
                ->groupby('year', 'month')
                ->limit(12)
                ->orderByDesc('created_at')
                ->get();

        } catch (\Throwable $e) {
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }


    // get full flashes
    public function get_mobile_flashes()
    {
        // TODO: Implement fetching full flashes from API and returning it
        try {
            return DB::table('flashes_models')
                ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'flashes_models.rubrique_id', '=', 'rubrique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'flashes_models.*'
                )
                ->orderBy('flashes_models.id', 'desc')
                ->limit(100)
                ->get();
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => $th->getMessage(),
                ]
            );
        }
    }



    public function get_mobile_rubriques()
    {
        try {
            return DB::table('rubrique_models')
                ->select('id', 'rubrique')
                ->get();
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => $th->getMessage(),
                ]
            );
        }
    }







    // store mobile abonne data to database

    // public function store_mobile_abonne_data(Request $request)
    // {
    //     try {
    //         if (empty($request->nom)):
    //             return response()->json(
    //                 [
    //                     'code' => 302,
    //                     'status' => 'erreur',
    //                     'message' => "Erreur! Le nom est obligatoire"
    //                 ]
    //             );
    //         endif;
    //         if (empty($request->prenom)):
    //             return response()->json(
    //                 [
    //                     'code' => 302,
    //                     'status' => 'erreur',
    //                     'message' => "Erreur! Le prénom est obligatoire"
    //                 ]
    //             );
    //         endif;

    //         if (empty($request->contact)):
    //             return response()->json(
    //                 [
    //                     'code' => 302,
    //                     'status' => 'erreur',
    //                     'message' => "Erreur! Le numéro de téléphone est obligatoire"
    //                 ]
    //             );
    //         endif;

    //         if (empty($request->email)):
    //             return response()->json(
    //                 [
    //                     'code' => 302,
    //                     'status' => 'erreur',
    //                     'message' => "Erreur! L'adresse email est obligatoire"
    //                 ]
    //             );
    //         endif;

    //         // check if email is valid
    //         if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)):
    //             return response()->json(
    //                 [
    //                     'code' => 302,
    //                     'status' => 'erreur',
    //                     'message' => "Erreur! L'adresse email n'est pas valide"
    //                 ]
    //             );
    //         endif;

    //         // check if email already exists in database
    //         $checkUser = DB::table('users')->where('email', $request->email)->first();

    //         if ($checkUser != null):
    //             return response()->json(
    //                 [
    //                     'code' => 302,
    //                     'status' => 'erreur',
    //                     'message' => "Erreur! L'adresse email est déjà utilisée pour un autre abonné"
    //                 ]
    //             );
    //         endif;

    //         // check if password is conform to password confirmation
    //         if ($request->password != $request->password_confirmation):
    //             return response()->json(
    //                 [
    //                     'code' => 302,
    //                     'status' => 'erreur',
    //                     'message' => "Erreur! Les mots de passe ne correspondent pas"
    //                 ]
    //             );
    //         endif;

    //         // generate a random password
    //         //$my_password = Str::random(10);

    //         // hash the password
    //         $hashed_password = password_hash($request->password, PASSWORD_BCRYPT);





    //         $store_user = new User();
    //         $store_user->email = $request->email;
    //         $store_user->phone = $request->contact;
    //         $store_user->user_device = $request->device_info;
    //         $store_user->user_type = "abonne";
    //         $store_user->password = $hashed_password;

    //         if ($store_user->save()):

    //             $store_abonne = new AbonnesMobileModels();

    //             $store_abonne->abonne_fname = $request->nom;
    //             $store_abonne->abonne_lname = $request->prenom;
    //             $store_abonne->user_id = $store_user->id;
    //             $store_abonne->type_abonne = "premium";
    //             $store_abonne->abonne_phone_number = $request->contact;
    //             $store_abonne->abonne_email = $request->email;

    //             $store_abonne->slug = CodeGenerator::generateRfk();

    //             if ($store_abonne->save()):

    //                 $credentials = request(['email', 'password']);

    //                 if (!$token = auth()->attempt($credentials)) {
    //                     return response()->json(
    //                         [
    //                             'code' => 302,
    //                             'status' => 'erreur',
    //                             'message' => "Oups! accès interdit !👺, Email ou mot de passe introuvable"
    //                         ]
    //                     );
    //                 }


    //                 $subject_email = "ALERTE INFO: NOTIFICATION APRES CREATION DE COMPTE";
    //                 $default_text = "L'agence de presse vous remercie pour la création de votre compte. Voici vos identifiants pour avoir accès votre espace.";

    //                 Mail::to($request->email)->send(new SendAccountMailer(
    //                     $request->password,
    //                     $store_abonne->abonne_fname . ' ' . $store_abonne->abonne_lname,
    //                     $store_abonne->abonne_email,
    //                     $subject_email,
    //                     $default_text
    //                 ));



    //                 return response()->json(
    //                     [
    //                         'status' => 'succès',
    //                         'code' => 200,
    //                         'account_data' => [
    //                             'nom' => $store_abonne->abonne_fname,
    //                             'prenom' => $store_abonne->abonne_lname,
    //                             'email' => $store_abonne->abonne_email,
    //                             'contact' => $store_abonne->abonne_phone_number,
    //                             'account_id' => $store_abonne->id,
    //                             'token' => $token,
    //                             'token_type' => 'Bearer',
    //                             'expires_in' => auth()->factory()->getTTL(),

    //                         ],
    //                         'message' => "Ok ! Le compte a été créé avec succès. Vous allez recevoir ses accès par mail."
    //                     ]
    //                 );
    //             else:

    //                 User::where('id', $store_user->id)->delete();

    //                 return response()->json(
    //                     [
    //                         'status' => 'error',
    //                         'code' => 300,
    //                         'message' => "Erreur ! Échec de la création du compte, veuillez réessayer!"
    //                     ]
    //                 );
    //             endif;

    //         else:
    //             User::where('id', $store_user->id)->delete();
    //             return response()->json(
    //                 [
    //                     'status' => 'error',
    //                     'code' => 300,
    //                     'message' => "Erreur ! Échec de la création du compte , veuillez réessayer!"
    //                 ]
    //             );
    //         endif;


    //     } catch (\Throwable $e) {
    //         return response()->json(
    //             [
    //                 'status' => 'error',
    //                 'code' => 300,
    //                 'message' => $e->getMessage(),
    //             ]
    //         );
    //     }
    // }


    //pour son inscription
    public function store_mobile_abonne_data(Request $request)
    {
        try {
            // Validation des données de la requête
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'contact' => 'required|string|max:20|unique:users,phone',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'device_info' => 'required|string|max:255'
            ]);

            // Gestion des erreurs de validation
            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => 'erreur',
                    'message' => "Erreur! Données invalides",
                    'errors' => $validator->errors()
                ], 422);
            }

            // Utilisation de transactions pour assurer la cohérence des données
            DB::beginTransaction();

            // Hachage du mot de passe
            $hashed_password = password_hash($request->password, PASSWORD_BCRYPT);

            // Création de l'utilisateur
            $store_user = new User();
            $store_user->email = $request->email;
            $store_user->phone = $request->contact;
            $store_user->user_device = $request->device_info;
            $store_user->user_type = "abonne";
            $store_user->password = $hashed_password;

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
            $store_abonne->abonne_fname = $request->nom;
            $store_abonne->abonne_lname = $request->prenom;
            $store_abonne->user_id = $store_user->id;
            $store_abonne->type_abonne = "premium";
            $store_abonne->abonne_phone_number = $request->contact;
            $store_abonne->abonne_email = $request->email;
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

            // Tentative d'authentification
            $credentials = request(['email', 'password']);
            if (!$token = auth()->attempt($credentials)) {
                return response()->json([
                    'code' => 401,
                    'status' => 'erreur',
                    'message' => "Oups! Accès interdit !👺, Email ou mot de passe introuvable"
                ], 401);
            }

            // Envoi de l'email de confirmation
            try {
                Mail::to($request->email)->send(new SendAccountMailer(
                    $request->password,
                    $store_abonne->abonne_fname . ' ' . $store_abonne->abonne_lname,
                    $store_abonne->abonne_email,
                    "ALERTE INFO: NOTIFICATION APRES CREATION DE COMPTE",
                    "L'agence de presse vous remercie pour la création de votre compte. Voici vos identifiants pour avoir accès à votre espace."
                ));
            } catch (Exception $e) {
                Log::error("Erreur lors de l'envoi de l'email de confirmation: " . $e->getMessage());
            }


            // Réponse en cas de succès
            return response()->json([
                'status' => 'succès',
                'code' => 200,
                'account_data' => [
                    'nom' => $store_abonne->abonne_fname,
                    'prenom' => $store_abonne->abonne_lname,
                    'email' => $store_abonne->abonne_email,
                    'contact' => $store_abonne->abonne_phone_number,
                    'account_id' => $store_abonne->id,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => auth()->factory()->getTTL(),
                ],
                'message' => "Ok ! Le compte a été créé avec succès. Vous allez recevoir ses accès par mail."
            ]);

        } catch (\Throwable $e) {
            // Rollback de la transaction en cas d'erreur
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function store_abonne_abonnements(Request $request)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();
            if (!$user) {
                return response()->json(['code' => 302, 'status' => 'erreur', 'message' => "Veuillez vous authentifier"]);
            }
            // return response()->json(data: ['status' => 'success', 'code' => 200, 'abonnement_data' => ['abonnement_code' => $request->all(), ]]);


            // Récupérer l'abonné
            $abonne = AbonnesMobileModels::where('user_id', $user->id)->first();
            if (!$abonne) {
                return response()->json(['code' => 404, 'status' => 'erreur', 'message' => "Abonné non trouvé"]);
            }

            // Vérifier les champs requis
            if (empty($request->forfait_id)) {
                return response()->json(['code' => 302, 'status' => 'erreur', 'message' => "Erreur! Le forfait de l'abonné est obligatoire"]);
            }
            if (empty($request->country_id) || sizeof($request->country_id) == 0) {
                return response()->json(['code' => 302, 'status' => 'erreur', 'message' => "Erreur! Le pays de l'abonné est obligatoire"]);
            }

            // Récupérer les informations du forfait
            $forfait_info = DB::table('forfaits_abonnements_mobile_models')->where('id', $request->forfait_id)->first();
            if (!$forfait_info) {
                return response()->json(['code' => 404, 'status' => 'erreur', 'message' => "Erreur! Forfait non trouvé"]);
            }

            // Calculer la date de fin
            $sizeOfCountry = sizeof($request->country_id);
            $dateline = 'P' . $forfait_info->duree_forfait . 'D';
            $date_fin = new DateTime();
            $date_fin->add(new DateInterval($dateline));
            $date_fin_formatted = $date_fin->format('Y-m-d H:i:s');

            // Vérifier si l'abonné a déjà un abonnement
            // $abonnement = AbonnementsMobileModels::where('abonne_id', $abonne->id)->first();
            // if ($abonnement) {
            //     // Mettre à jour l'abonnement existant
            //     $abonnement->abonne_forfait_id = $request->forfait_id;
            //     $abonnement->abonne_country_id = implode(',', $request->country_id);
            //     $abonnement->montant_abonnements = (int)$sizeOfCountry * $forfait_info->montant_forfait;
            //     $abonnement->date_fin = $date_fin_formatted;
            //     $abonnement->slug = CodeGenerator::generateSlugCode();
            //     $abonnement->save();
            // } else {
            // Créer un nouvel abonnement
            $abonnement = new AbonnementsMobileModels();
            $abonnement->abonnement_code = CodeGenerator::generateAbonnementCodeUnique();
            $abonnement->abonne_id = $abonne->id;
            $abonnement->abonne_forfait_id = $request->forfait_id;
            $abonnement->abonne_country_id = implode(',', $request->country_id);
            $abonnement->montant_abonnements = (int) $sizeOfCountry * $forfait_info->montant_forfait;
            $abonnement->date_debut = now();
            $abonnement->date_fin = $date_fin_formatted;

            $abonnement->customer_country = $request->customer_country;
            $abonnement->customer_zip_code = $request->customer_zip_code;
            $abonnement->country_iso_code = $request->country_iso_code;
            $abonnement->state_iso_code = $request->state_iso_code;

            $abonnement->slug = CodeGenerator::generateSlugCode();
            $abonnement->save();
            // }

            // Mettre à jour le statut de l'abonné
            DB::table('abonnes_mobile_models')->where('id', $abonne->id)->update(['status_abonnement' => 1]);

            // Envoyer la notification par email
            $abonne_info = AbonnesMobileModels::where('id', $abonne->id)->first();
            $abonne_pays_id = explode(',', $abonnement->abonne_country_id);
            $abonne_country = CountriesModels::whereIn('id', $abonne_pays_id)->pluck('pays')->toArray();
            // $subject_email = "ALERTE INFO: NOTIFICATION APRES ABONNEMENT";
            // $notifications = "L'agence de presse vous remercie pour votre demande d'abonnement. Voici les informations de votre abonnement:" . "<br><br>" .
            //     "<strong>Nom et prénom de l'abonné:</strong> " . $abonne_info->abonne_fname . " " . $abonne_info->abonne_lname . "<br><br>" .
            //     "<strong>Montant de l'abonnement:</strong> " . $abonnement->montant_abonnements . "<br><br>" .
            //     "<strong>Les pays de l'abonnement:</strong> " . implode(", ", $abonne_country) . "<br><br>" .
            //     "<strong>Date fin d'échéance de l'abonnement:</strong> " . $date_fin_formatted;

            // try {
            //     Mail::to($abonne_info->abonne_email)->send(new Notifications($notifications, $subject_email));
            // } catch (\Exception $e) {
            //     return response()->json(['status' => 'error', 'code' => 500, 'message' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()]);
            // }

            return response()->json(['status' => 'success', 'code' => 200, 'abonnement_data' => ['abonnement_code' => $abonnement->abonnement_code, 'date_fin' => $abonnement->date_fin]]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => 500, 'message' => $e->getMessage()]);
        }
    }




    // Récupère les dépêches par rubrique
    public function get_mobile_depeche_by_rubrique($rubrique_id)
    {
        try {
            // Récupérer les dépêches avec les détails du pays et de la rubrique
            $depeches = DepecheModels::join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'depeche_models.id',
                    'depeche_models.author',
                    'depeche_models.rubrique_id',
                    'depeche_models.genre_id',
                    'depeche_models.pays_id',
                    'depeche_models.titre',
                    'depeche_models.lead',
                    'depeche_models.legende',
                    'depeche_models.media_url',
                    'depeche_models.contenus',
                    'depeche_models.counter',
                    'depeche_models.status',
                    'depeche_models.slug',
                    'depeche_models.created_at',
                    'depeche_models.updated_at'
                )
                ->where('depeche_models.rubrique_id', $rubrique_id)
                ->where('depeche_models.status', 1)
                ->orderBy('depeche_models.created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $depeches
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rubrique non trouvée.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la récupération des dépêches.'
            ], 500);
        }
    }


    // Récupère les dépêches par pays du client
    public function get_mobile_depeche_by_country($country_id)
    {
        try {
            $depeches = DepecheModels::where('country_id', $country_id)
                ->where('status', 1)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $depeches
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pays non trouvé.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la récupération des dépêches.'
            ], 500);
        }
    }

    // Récupère les archives des dépêches
    public function get_mobile_depeche_archives()
    {
        try {
            $archives = DepecheModels::where('status', 1)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy(function ($date) {
                    return \Carbon\Carbon::parse($date->created_at)->format('Y-m');
                });

            return response()->json([
                'status' => 'success',
                'data' => $archives
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la récupération des archives.'
            ], 500);
        }
    }

    // Récupère les données d'archives des dépêches pour un mois et une année spécifiques
    public function get_mobile_depeche_archives_data($month, $year)
    {
        try {
            $depeches = DepecheModels::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->where('status', 1)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $depeches
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune dépêche trouvée pour cette période.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la récupération des dépêches.'
            ], 500);
        }
    }

    public function get_mobile_countries_list()
    {
        try {
            $ids = [1, 2, 3];
            $countries = CountriesModels::whereIn('id', $ids)->get();

            return response()->json([
                'status' => 'success',
                'data' => $countries
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la récupération des pays.'
            ], 500);
        }
    }

    public function forfaitsList()
    {
        try {
            return ForfaitsAbonnementsMobileModels::where('is_active', 1)->get();
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

    public function notify(Request $request)
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

                if ($object_data->code == '00') {
                    $ifTransactionExist = TransactionsModels::where('transaction_id', $request->cpm_trans_id)->exists();

                    if (!$ifTransactionExist) {
                        $add_transaction = new TransactionsModels();
                        $add_transaction->transaction_id = $request->cpm_trans_id;
                        $add_transaction->montant = $object_data->data['amount'];
                        $add_transaction->operations = $object_data->data['description'];
                        $add_transaction->method_payment = $object_data->data['payment_method'];
                        $add_transaction->date_transaction = date('Y-m-d H:i:s', strtotime($object_data->data['payment_date']));
                        $add_transaction->status = $object_data->data['status'];
                        $add_transaction->save();

                        $is_abonnement = DB::table('abonnements_mobile_models')->where('abonnement_code', $request->cpm_trans_id)->first();

                        if ($is_abonnement != null) {
                            DB::table('abonnements_mobile_models')->where('abonnement_code', $request->cpm_trans_id)->update([
                                'updated_at' => now(),
                                'payments' => 1,
                            ]);

                            // Trouver l'utilisateur associé à cet abonnement
                            $user = AbonnesMobileModels::find($is_abonnement->abonne_id);
                            $forfait = ForfaitsAbonnementsMobileModels::find($is_abonnement->abonne_forfait_id);

                            if ($user && $forfait) {
                                try {
                                    $message = "Votre abonnement {$forfait->forfait_libelle} a été effectué avec succès. Il expire le {$user->date_fin}.";

                                    // Récupérer le token de l'appareil pour l'utilisateur
                                    $device = UserDevice::where('user_id', $user->user_id)->first();

                                    if ($device) {
                                        // Envoi de la notification
                                        $this->sendNotification(
                                            $device->device_id, // Utiliser le token de l'appareil
                                            'Abonnement effectué',
                                            $message
                                        );

                                        // Sauvegarder les données de la notification
                                        NotificationPush::create([
                                            'device_id' => $device->device_id,
                                            'type' => 'abonnement',
                                            'title' => 'Abonnement effectué',
                                            'body' => $message,
                                            'sent' => true,
                                        ]);
                                    } else {
                                        // Sauvegarder les données de la notification en cas d'absence de device
                                        NotificationPush::create([
                                            'device_id' => null,
                                            'type' => 'abonnement',
                                            'title' => 'Abonnement effectué',
                                            'body' => "Aucun appareil trouvé pour l'utilisateur.",
                                            'sent' => false,
                                        ]);
                                    }
                                } catch (\Throwable $th) {
                                    Log::error('Erreur lors du traitement du paiement : ' . $th->getMessage());

                                    // Sauvegarder les données de la notification en cas d'erreur
                                    NotificationPush::create([
                                        'device_id' => $device->device_id ?? null,
                                        'type' => 'abonnement',
                                        'title' => 'Abonnement effectué',
                                        'body' => "Erreur lors de l'envoi de la notification",
                                        'sent' => false,
                                    ]);
                                }

                            } else {
                                Log::error('Utilisateur ou forfait non trouvé pour l\'abonnement ID: ' . $is_abonnement->abonne_id);
                            }

                        }

                        $solde = DB::table('solde_models')->orderByDesc('id')->first();

                        if ($solde->montants == 0) {
                            DB::table('solde_models')->update([
                                'montants' => (int) $object_data->data['amount'],
                                'slug' => CodeGenerator::generateSlugCode()
                            ]);
                        } else {
                            DB::table('solde_models')->update([
                                'montants' => (int) $solde->montants + (int) $object_data->data['amount'],
                                'slug' => CodeGenerator::generateSlugCode()
                            ]);
                        }

                        return response()->json([
                            'status' => 'success',
                            'code' => 200,
                            'message' => $request->cpm_trans_id
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 'erreur',
                            'code' => 400,
                            'message' => "Cette transaction existe déjà"
                        ], 400);
                    }
                }

                if ($object_data->code == '627') {
                    $add_transaction = new TransactionsModels();
                    $add_transaction->transaction_id = $request->cpm_trans_id;
                    $add_transaction->montant = $object_data->data['amount'];
                    $add_transaction->operations = $object_data->data['description'];
                    $add_transaction->method_payment = $object_data->data['payment_method'];
                    $add_transaction->date_transaction = date('Y-m-d H:i:s', strtotime($object_data->data['payment_date']));
                    $add_transaction->status = $object_data->data['status'];
                    $add_transaction->save();
                }

            } catch (\Throwable $e) {
                Log::error('Erreur lors du traitement du paiement : ' . $e->getMessage());
                return response()->json([
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 'erreur',
                'code' => 400,
                'message' => "cpm_trans_id non fourni"
            ], 400);
        }
    }

    private function sendNotification($userId, $title, $body)
    {
        $user = User::find($userId);
        if ($user && $user->user_device) {
            $data = [
                'app_id' => env('ONESIGNAL_APP_ID'),
                'include_player_ids' => [$user->user_device],
                'headings' => ['en' => $title],
                'contents' => ['en' => $body],
                'small_icon' => 'ic_stat_icon_monochrome'
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', $data);

            return $response->json();
        }
    }



    public function getAbonnementDetails()
    {
        try {
            // Récupérer l'abonnement en utilisant le code d'abonnement et charger les relations
            $abonnement = AbonnementsMobileModels::where('user_id', Auth::user()->id)
                ->with(['forfait', 'abonne'])  // Charger les relations forfait et abonné
                ->first();


            if (!$abonnement) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 404,
                    'message' => 'Abonnement non trouvé'
                ], 404);
            }

            // Récupérer les IDs des pays liés à l'abonnement
            $pays_ids = explode(',', $abonnement->abonne_country_id);

            // Récupérer les noms des pays à partir des IDs
            $pays = CountriesModels::whereIn('id', $pays_ids)->get();

            // Calculer si l'abonnement est toujours valide
            $isValid = now()->between($abonnement->date_debut, $abonnement->date_fin);

            // Retourner les informations de l'abonnement
            return response()->json([
                'status' => 'succès',
                'code' => 200,
                'abonnement' => [
                    'abonnement_code' => $abonnement->abonnement_code,
                    'date_debut' => $abonnement->date_debut,
                    'date_fin' => $abonnement->date_fin,
                    'montant_abonnements' => $abonnement->montant_abonnements,
                    'forfait' => $abonnement->forfait,  // Détails du forfait
                    'countries' => $pays,  // Liste des pays
                    'countriesId' => $pays_ids,  // Liste des IDs des pays
                    'isValid' => $isValid,  // Statut de validité de l'abonnement
                    'abonne' => $abonnement->abonne  // Détails de l'abonné
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getLatestAbonnementDetails()
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();

            // Vérifier si l'utilisateur est abonné
            $abonne = AbonnesMobileModels::where('user_id', $user->id)->first();
            if (!$abonne) {
                return response()->json(['status' => 'erreur', 'code' => 404, 'message' => 'Aucun abonnement trouvé pour cet utilisateur'], 404);
            }

            // Récupérer tous les abonnements valides
            $abonnements = AbonnementsMobileModels::where('abonne_id', $abonne->id)
                ->where('date_fin', '>=', now()) // Filtrer les abonnements valides
                ->where('payments', 1)
                ->with(['forfait', 'abonne']) // Charger les relations forfait et abonné
                ->orderBy('date_fin', 'asc') // Trier par date de fin (le plus proche en premier)
                ->get();

            // Si aucun abonnement valide n'est trouvé
            if ($abonnements->isEmpty()) {
                return response()->json(['status' => 'erreur', 'code' => 404, 'message' => 'Aucun abonnement valide trouvé pour cet utilisateur'], 404);
            }

            // Calculer le nombre total de jours restants
            $totalJoursRestants = $abonnements->sum(function ($abonnement) {
                return now()->diffInDays($abonnement->date_fin);
            });

            // Préparer une liste de pays avec abonnements valides
            $paysValides = [];

            // Préparer les abonnements valides avec les informations nécessaires
            $validAbonnements = $abonnements->map(function ($abonnement) use (&$paysValides) {
                // Récupérer les IDs des pays liés à l'abonnement
                $pays_ids = explode(',', $abonnement->abonne_country_id);

                // Récupérer les noms des pays à partir des IDs
                $pays = CountriesModels::whereIn('id', $pays_ids)->get();

                // Ajouter les pays valides
                foreach ($pays as $paysData) {
                    $paysValides[$paysData->id] = $paysData->pays; // Éviter les doublons en utilisant l'ID du pays comme clé
                }

                // Calculer si l'abonnement est toujours valide
                $isValid = now()->between($abonnement->date_debut, $abonnement->date_fin);

                return [
                    'abonnement_code' => $abonnement->abonnement_code,
                    'date_debut' => $abonnement->date_debut,
                    'date_fin' => $abonnement->date_fin,
                    'montant_abonnements' => $abonnement->montant_abonnements,
                    'forfait' => $abonnement->forfait, // Détails du forfait
                    'countries' => $pays, // Liste des pays
                    'countriesId' => $pays_ids, // Liste des IDs des pays
                    'isValid' => $isValid, // Statut de validité de l'abonnement
                    'abonne' => $abonnement->abonne // Détails de l'abonné
                ];
            });

            // Résumé des abonnements valides
            $abonnementResume = [
                'nombre_abonnements_valides' => $abonnements->count(),
                'jours_restants_total' => $totalJoursRestants, // Nombre total de jours restants
                'pays_valides' => array_values($paysValides) // Liste des pays où les abonnements sont valides (sans doublons)
            ];

            // Vérifier s'il y a au moins un abonnement valide
            $hasValidAbonnement = $validAbonnements->contains('isValid', true);

            // Retourner la réponse avec le résumé et les abonnements valides
            return response()->json([
                'status' => 'succès',
                'code' => 200,
                'resume_abonnement' => $abonnementResume,  // Résumé des abonnements
                'valid_abonnements' => $validAbonnements,  // Liste des abonnements valides
                'isValid' => $hasValidAbonnement // Statut global de validité des abonnements
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'erreur', 'code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    // public function updateDeviceToken(Request $request)
    // {
    //     $deviceId = $request->input('device_id');
    //     $deviceToken = $request->input('device_token');
    //     $platform = $request->input('platform'); // Optionnel, par exemple 'iOS' ou 'Android'

    //     // Vérifier si l'utilisateur est authentifié
    //     $user = Auth::check() ? Auth::user() : null;

    //     // Mettre à jour ou créer un nouvel enregistrement dans la table devices
    //     UserDevice::updateOrCreate(
    //         ['device_id' => $deviceId], // Critère de recherche basé sur le device_id
    //         [
    //             'user_id' => $user ? $user->id : null, // Lier à l'utilisateur s'il est authentifié
    //             'token' => $deviceToken, // Mettre à jour le token
    //             'platform' => $platform, // Optionnel : ajouter la plateforme
    //         ]
    //     );

    //     return response()->json(['status' => 'succès', 'message' => $deviceId]);
    // }

    public function updateDeviceToken(Request $request)
{
    try {
        $deviceId = $request->input('device_id');
        $deviceToken = $request->input('device_token');
        $platform = $request->input('platform');

        Log::info("Tentative de mise à jour du device token", [
            'device_id' => $deviceId,
            'platform' => $platform
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            if ($user) {
                // Logique existante pour les utilisateurs authentifiés
                Log::info("Utilisateur authentifié", ['user_id' => $user->id]);

                if ($user->connected && $user->user_device !== $deviceId) {
                    Log::info("L'utilisateur est déjà connecté sur un autre appareil", [
                        'user_id' => $user->id,
                        'old_device' => $user->user_device,
                        'new_device' => $deviceId
                    ]);

                    $this->sendDisconnectNotification($user->user_device);
                    Log::info("Notification de déconnexion envoyée", ['old_device' => $user->user_device]);

                    UserDevice::where('device_id', $user->user_device)->delete();
                    Log::info("Ancien enregistrement d'appareil supprimé", ['old_device' => $user->user_device]);
                }

                // Mise à jour des informations de l'utilisateur
                $user->update([
                    'user_device' => $deviceId,
                    'connected' => 1
                ]);
                Log::info("Informations de l'utilisateur mises à jour", [
                    'user_id' => $user->id,
                    'new_device' => $deviceId
                ]);

                // Mise à jour de l'enregistrement dans la table devices
                UserDevice::updateOrCreate(
                    ['device_id' => $deviceId],
                    [
                        'user_id' => $user->id,
                        'token' => $deviceToken,
                        'platform' => $platform,
                    ]
                );
            }else {
                // Nouvel utilisateur non authentifié
                Log::info("Utilisateur non authentifié, vérification de l'appareil");

                $existingDevice = UserDevice::where('device_id', $deviceId)->first();

                if ($existingDevice) {
                    // L'appareil existe déjà, vérifions si le token a changé
                    if ($existingDevice->token !== $deviceToken) {
                        Log::info("Mise à jour du token pour l'appareil existant", [
                            'device_id' => $deviceId,
                            'old_token' => $existingDevice->token,
                            'new_token' => $deviceToken
                        ]);

                        $existingDevice->update([
                            'token' => $deviceToken,
                            'platform' => $platform,
                        ]);
                    } else {
                        Log::info("Le token de l'appareil n'a pas changé", ['device_id' => $deviceId]);
                    }
                } else {
                    // Nouvel appareil, création de l'enregistrement
                    Log::info("Création d'un nouvel enregistrement pour l'appareil", ['device_id' => $deviceId]);

                    UserDevice::create([
                        'device_id' => $deviceId,
                        'token' => $deviceToken,
                        'platform' => $platform,
                    ]);
                }
            }

            DB::commit();
            Log::info("Transaction réussie pour la mise à jour du device token");

            return response()->json([
                'status' => 'succès',
                'message' => 'Appareil enregistré avec succès'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la transaction de mise à jour du device token", [
                'error' => $e->getMessage(),
                'device_id' => $deviceId
            ]);
            throw $e;
        }

    } catch (\Throwable $e) {
        Log::error('Erreur lors de la mise à jour du device token', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'status' => 'erreur',
            'code' => 500,
            'message' => 'Une erreur est survenue lors de la mise à jour de l\'appareil. Veuillez réessayer plus tard.',
        ], 500);
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
            'data' => [ // Ajouter les additionalData, y compris le type "disconnect"
                'type' => 'disconnect'
            ],
        ];

        // Envoyer la requête à OneSignal
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', $data);

        return $response->json();
    }


    public function logoutDevice(Request $request)
    {
        try {
            // Vérifier si l'utilisateur est authentifié
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 'erreur',
                    'message' => "Utilisateur non authentifié."
                ], 401);
            }



            try {
                // Réinitialiser le device_id de l'utilisateur
                $user->user_device = null;
                $user->connected = 0;

                $user->save();
            } catch (Exception $e) {
                Log::error("Erreur lors de la réinitialisation du device_id : " . $e->getMessage());
                return response()->json([
                    'status' => 'erreur',
                    'message' => "Impossible de réinitialiser l'identifiant de l'appareil."
                ], 500);
            }
            try {
                // Supprimer l'enregistrement de l'appareil actuel
                UserDevice::where('user_id', $user->id)
                    ->where('device_id', $user->device_id)
                    ->delete();
            } catch (Exception $e) {
                Log::error("Erreur lors de la suppression de l'enregistrement de l'appareil : " . $e->getMessage());
                return response()->json([
                    'status' => 'erreur',
                    'message' => "Impossible de supprimer l'appareil de l'utilisateur."
                ], 500);
            }

            return response()->json([
                'status' => 'succès',
                'message' => 'Appareil déconnecté avec succès'
            ]);

        } catch (\Throwable $e) {
            // Enregistrer l'erreur si quelque chose d'autre se produit
            Log::error("Erreur lors de la déconnexion de l'appareil : " . $e->getMessage());
            return response()->json([
                'status' => 'erreur',
                'message' => 'Une erreur est survenue lors de la déconnexion de l\'appareil. Veuillez réessayer.'
            ], 500);
        }
    }


    public function sendNotificationsToAllUsers(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'body' => 'required|min:3|max:1000',
            ]);

            $title = $validatedData['title'];
            $body = $validatedData['body'];

            // Récupération de tous les appareils enregistrés
            $devices = UserDevice::where('device_id', "c7680eb4-2ef5-492b-ba95-f9746a75ea56")->first();

            // foreach ($devices as $device) {
                $notificationData = [
                    'device_id' => $devices->device_id,
                    'type' => 'general',
                    'title' => $title,
                    'body' => $body,
                    'sent' => false,
                ];

                // $notificationService = new NotificationPushService();
                // $notificationService->sendGeneralNotification($title, $body);


                try {
                    // Envoi de la notification
                    // $this->sendNotificationAll($devices->device_id, $title, $body);

                    $payload = [
                        'title' => $title,
                        'body' => $body,
                        'pays_id' => $request->pays_id,
                        'media' => $request->media,
                        'slug' => $request->slug,
                    ];

                    // Envoi de la notification en utilisant le payload dynamique
                    $this->sendNotificationAll($devices->device_id, $payload);
                    $notificationData['sent'] = true;
                } catch (\Throwable $e) {
                    Log::error("Erreur lors de l'envoi de la notification à l'appareil ID: {$devices->device_id} : " . $e->getMessage());
                }

                // Sauvegarde des données de la notification
                NotificationPush::create($notificationData);
            // }

            return response()->json(['status' => 'success', 'message' => 'Notifications envoyées avec succès.'.$payload]);
        } catch (\Throwable $e) {
            Log::error('Erreur lors de l\'envoi des notifications: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // private function sendNotificationAll($deviceToken, $title, $body)
    // {
    //     // Logique pour envoyer une notification via OneSignal
    //     $data = [
    //         'app_id' => env('ONESIGNAL_APP_ID'),
    //         'include_player_ids' => [$deviceToken],
    //         'headings' => ['en' => $title],
    //         'contents' => ['en' => $body],
    //         'small_icon' => 'ic_stat_icon_monochrome', // Remplacer avec l'icône souhaitée
    //     ];

    //     // Envoyer la requête à OneSignal
    //     $response = Http::withHeaders([
    //         'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
    //         'Content-Type' => 'application/json',
    //     ])->post('https://onesignal.com/api/v1/notifications', $data);

    //     return $response->json();
    // }

    private function sendNotificationAll($deviceToken, $payload)
{
    // Assurez-vous que le payload contient un titre et un contenu
    $title = $payload['title'] ?? 'Notification';
    $body = $payload['body'] ?? '';

    // Préparer les données de base pour la notification
    $data = [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'include_player_ids' => [$deviceToken],
        'headings' => ['en' => $title],
        'contents' => ['en' => $body],
        'small_icon' => 'ic_stat_icon_monochrome', // Remplacer par l'icône souhaitée
        'data' => [
            'type' => 'depeche',
            'slug' => $payload['slug'],
            'paysId' => $payload['pays_id']
        ],
    ];

    // Envoyer la requête à OneSignal
    $response = Http::withHeaders([
        'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
        'Content-Type' => 'application/json',
    ])->post('https://onesignal.com/api/v1/notifications', $data);

    return $response->json();
}




    public function getUserNotifications(Request $request)
    {
        try {
            $notifications = NotificationPush::where(function ($query) use ($request) {
                // if ($request->user_id) {
                //     $query->where('user_id', $request->user_id)
                //           ->orWhere('device_id', $request->device_id);
                // } else {
                $query->where('device_id', $request->device_id);
                // }
            })
                ->where('type', '!=', 'abonnement')
                ->orderBy('created_at', 'desc')
                ->take(30)
                ->get();
            // ->unique('notification_id');

            return response()->json([
                'status' => 'success',
                'data' => $notifications,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }



    public function getDepechesAndFlashesForMonth(Request $request)
    {
        try {
            // Validation des paramètres d'entrée
            $request->validate([
                'year' => 'required|integer',
                'month' => 'required|integer|min:1|max:12',
            ]);

            // Récupération des valeurs de l'année et du mois
            $year = $request->year;
            $month = $request->month;

            // Formatage des dates de début et de fin du mois
            $startDate = "{$year}-{$month}-01";
            $endDate = date("Y-m-t", strtotime($startDate)); // Récupère le dernier jour du mois

            // Récupération des dépêches pour le mois donné
            $depeche_data = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'depeche_models.*'
                )
                ->whereBetween('depeche_models.created_at', [$startDate, $endDate])
                ->orderBy('depeche_models.id', 'desc')
                ->get();

            // Récupération des flashs pour le mois donné
            $flash_data = DB::table('flashes_models')
                ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'flashes_models.rubrique_id', '=', 'rubrique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubrique_models.rubrique',
                    'flashes_models.*'
                )
                ->whereBetween('flashes_models.created_at', [$startDate, $endDate])
                ->orderBy('flashes_models.id', 'desc')
                ->get();

            // Retour des données au format JSON
            return response()->json([
                'depeche_data' => $depeche_data,
                'flashes_data' => $flash_data,
            ], 200);

        } catch (\Throwable $th) {
            // Gestion des erreurs
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            // Récupération de l'utilisateur authentifié
            $user = Auth::user();

            // Validation des données
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'contact' => 'required|string|max:20|unique:users,phone,' . $user->id,
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Erreur! Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Utilisation de transactions pour assurer la cohérence des données
            DB::beginTransaction();

            // Mise à jour des informations de l'utilisateur
            $user->email = $request->email;
            $user->phone = $request->contact;

            if (!$user->save()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => "Erreur! Échec de la mise à jour de l'utilisateur."
                ], 500);
            }

            // Mise à jour des informations de l'abonné
            $abonne = AbonnesMobileModels::where('user_id', $user->id)->first();

            if ($abonne) {
                $abonne->abonne_fname = $request->nom;
                $abonne->abonne_lname = $request->prenom;
                $abonne->abonne_phone_number = $request->contact;
                $abonne->abonne_email = $request->email;

                if (!$abonne->save()) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'code' => 500,
                        'message' => "Erreur! Échec de la mise à jour de l'abonné."
                    ], 500);
                }
            }

            // Commit de la transaction si tout est bon
            DB::commit();

            // Réponse en cas de succès
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => "Les informations de profil ont été mises à jour avec succès.",
                'data' => [
                    'nom' => $abonne->abonne_fname,
                    'prenom' => $abonne->abonne_lname,
                    'email' => $abonne->abonne_email,
                    'contact' => $abonne->abonne_phone_number
                ]
            ]);
        } catch (\Throwable $e) {
            // Rollback de la transaction en cas d'erreur
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        // Validation des données entrantes
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Erreur! Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Récupérer l'utilisateur connecté
            $user = auth()->user();

            // Vérifier que le mot de passe actuel est correct
            if (!\Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Mot de passe actuel incorrect',
                ], 401);
            }

            // Mettre à jour le mot de passe
            $user->password = bcrypt($request->new_password);
            $user->save();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Mot de passe mis à jour avec succès',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Erreur lors de la mise à jour du mot de passe',
            ], 500);
        }
    }


    public function storeProblemeReport(Request $request)
    {
        try {
            $user = Auth::user();

            // Validation du formulaire
            if (empty($request->title)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => "302",
                    'message' => "Le titre du problème est obligatoire."
                ]);
            }

            if (empty($request->description)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => "302",
                    'message' => "La description du problème est obligatoire."
                ]);
            }

            if (empty($request->category)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => "302",
                    'message' => "La catégorie du problème est obligatoire."
                ]);
            }

            $problemReport = new ProblemReport();
            $problemReport->title = $request->title;
            $problemReport->description = $request->description;
            $problemReport->category = $request->category;
            $problemReport->user_id = $user->id;

            if ($problemReport->save()) {
                // Gestion des images
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        $imageUrl = $this->uploadProblemImage($image);

                        if ($imageUrl == "error") {
                            return response()->json([
                                'status' => 'erreur',
                                'code' => "302",
                                'message' => "L'enregistrement d'une image a échoué."
                            ]);
                        }

                        if ($imageUrl != "file_not_found") {
                            ProblemReportImage::create([
                                'problem_report_id' => $problemReport->id,
                                'image_path' => $imageUrl,
                            ]);
                        }
                    }
                }

                return response()->json([
                    'status' => 'succès',
                    'code' => 200,
                    'message' => "Ok! Le problème a été signalé avec succès.",
                    'data' => $problemReport->load('images'),
                ],200);
            } else {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 300,
                    'message' => "Erreur ! Échec de l'enregistrement du problème, veuillez réessayer!"
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 302,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function uploadProblemImage($image)
    {
        return UploadService::upload_problem_image($image);
    }

    public function listProblemeReport()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['code' => 401, 'status' => 'erreur', 'message' => "Veuillez vous authentifier"]);
        }
        $problemReport = ProblemReport::with('images')->latest()->where('user_id', $user->id)->get();
        return response()->json([
            'status' => 'success',
            'data' => $problemReport,
        ], 200);
    }

    public function showProblemeReport($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['code' => 302, 'status' => 'erreur', 'message' => "Veuillez vous authentifier"]);
        }
        $problemReport = ProblemReport::with('images')->where('user_id', $user->id)->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $problemReport,
        ], 200);
    }

    public function storeContactForm(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'required|string|min:10|max:15',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $contactMessage = ContactMessage::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Votre message a été envoyé avec succès.',
            'data' => $contactMessage,
        ], 200);
    }

    public function searchDepeches(Request $request)
    {
        try {
            // Construction de la requête
            $query = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'depeche_models.*');

            // Ajout de la condition de recherche si un terme de recherche est fourni
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->input('search');
                $query->where('depeche_models.titre', 'like', '%' . $searchTerm . '%')
                    ->orWhere('depeche_models.contenus', 'like', '%' . $searchTerm . '%');
            }

            // Ajout de la condition de filtrage des pays si fourni
            if ($request->has('customer_countries_id') && $request->customer_countries_id != 'undifined') {
                $countryArray = explode(',', $request->customer_countries_id);
                $query->whereIn('depeche_models.pays_id', $countryArray);
            }

            // Limiter le nombre de résultats et trier par date
            $depecheData = $query->orderBy('depeche_models.id', 'desc')->limit(15)->get();

            return response()->json([
                'status' => 'success',
                'data' => $depecheData,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function searchFlashes(Request $request)
    {
        try {
            // Construction de la requête
            $query = DB::table('flashes_models')
                ->join('countries_models', 'flashes_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'flashes_models.rubrique_id', '=', 'rubrique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'flashes_models.*');

            // Ajout de la condition de recherche si un terme de recherche est fourni
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = strtoupper($request->input('search'));
                $query->where('flashes_models.contenus', 'like', '%' . $searchTerm . '%');
            }

            // Ajout de la condition de filtrage des pays si fourni
            if ($request->has('customer_countries_id') && $request->customer_countries_id != 'undifined') {
                $countryArray = explode(',', $request->customer_countries_id);
                $query->whereIn('flashes_models.pays_id', $countryArray);
            }

            // Limiter le nombre de résultats et trier par date
            $flashData = $query->orderBy('flashes_models.id', 'desc')->limit(10)->get();

            return response()->json([
                'status' => 'success',
                'data' => $flashData,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Paiement via GeniusPay

    public function store_abonne_abonnements2(Request $request)
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = Auth::user();
            if (!$user) {
                return response()->json(['code' => 302, 'status' => 'erreur', 'message' => "Veuillez vous authentifier"]);
            }
            // return response()->json(data: ['status' => 'success', 'code' => 200, 'abonnement_data' => ['abonnement_code' => $request->all(), ]]);


            // Récupérer l'abonné
            $abonne = AbonnesMobileModels::where('user_id', $user->id)->first();
            if (!$abonne) {
                return response()->json(['code' => 404, 'status' => 'erreur', 'message' => "Abonné non trouvé"]);
            }

            // Vérifier les champs requis
            if (empty($request->forfait_id)) {
                return response()->json(['code' => 302, 'status' => 'erreur', 'message' => "Erreur! Le forfait de l'abonné est obligatoire"]);
            }
            if (empty($request->country_id) || sizeof($request->country_id) == 0) {
                return response()->json(['code' => 302, 'status' => 'erreur', 'message' => "Erreur! Le pays de l'abonné est obligatoire"]);
            }

            // Récupérer les informations du forfait
            $forfait_info = DB::table('forfaits_abonnements_mobile_models')->where('id', $request->forfait_id)->first();
            if (!$forfait_info) {
                return response()->json(['code' => 404, 'status' => 'erreur', 'message' => "Erreur! Forfait non trouvé"]);
            }

            // Calculer la date de fin
            $sizeOfCountry = sizeof($request->country_id);
            $dateline = 'P' . $forfait_info->duree_forfait . 'D';
            $date_fin = new DateTime();
            $date_fin->add(new DateInterval($dateline));
            $date_fin_formatted = $date_fin->format('Y-m-d H:i:s');
            // Créer un nouvel abonnement
            $abonnement = new AbonnementsMobileModels();
            $abonnement->abonnement_code = CodeGenerator::generateAbonnementCodeUnique();
            $abonnement->abonne_id = $abonne->id;
            $abonnement->abonne_forfait_id = $request->forfait_id;
            $abonnement->abonne_country_id = implode(',', $request->country_id);
            $abonnement->montant_abonnements = (int) $sizeOfCountry * $forfait_info->montant_forfait;
            $abonnement->date_debut = now();
            $abonnement->date_fin = $date_fin_formatted;

            $abonnement->customer_country = $request->customer_country;
            $abonnement->customer_zip_code = $request->customer_zip_code;
            $abonnement->country_iso_code = $request->country_iso_code;
            $abonnement->state_iso_code = $request->state_iso_code;

            $abonnement->slug = CodeGenerator::generateSlugCode();
            // $abonnement->payment_reference = $json['data']['reference'];
            $abonnement->save();
            $payment = $this->geniusPayAbonnementAction(
                $abonnement,
                $abonne,
                'Abonnement mobile Alerte Info'
            );
            Log::info('On est dans la bonne fonction MOBILE RESPONSE');

            if (!$payment['status']) {

                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $payment['message']
                ]);
            }

            // Mettre à jour le statut de l'abonné
            // DB::table('abonnes_mobile_models')->where('id', $abonne->id)->update(['status_abonnement' => 1]);

            // Envoyer la notification par email
            $abonne_info = AbonnesMobileModels::where('id', $abonne->id)->first();
            $abonne_pays_id = explode(',', $abonnement->abonne_country_id);
            $abonne_country = CountriesModels::whereIn('id', $abonne_pays_id)->pluck('pays')->toArray();

            $abonnement->payment_reference = $payment['reference'];
            $abonnement->save();
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'abonnement_data' => [
                    'abonnement_code' => $abonnement->abonnement_code,
                    'date_fin' => $abonnement->date_fin,
                    'payment_url' => $payment['payment_url'],
                    'reference' => $payment['reference']
                ]
            ]);
            // return response()->json(['status' => 'success', 'code' => 200, 'abonnement_data' => ['abonnement_code' => $abonnement->abonnement_code, 'date_fin' => $abonnement->date_fin]]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'code' => 500, 'message' => $e->getMessage()]);
        }
    }
    private function geniusPayAbonnementAction(
        $abonnement,
        $abonne,
        $description
    )
    {
        try {

            $payload = [
                'amount' => (int) $abonnement->montant_abonnements,
                'description' => $description,

                'customer' => [
                    'name' => $abonne->abonne_fname . ' ' . $abonne->abonne_lname,
                    'email' => $abonne->abonne_email,
                    'phone' => $abonne->abonne_phone_number,
                    'country' => 'CI'
                ],

                'currency' => 'XOF',
                // Pour mobile, on peut laisser les mêmes URLs
                'success_url' => GeniusMarchand::getSuccessUrl(),
                'error_url' => GeniusMarchand::getErrorUrl(),
                'notify_url' => GeniusMarchand::getWebhookUrl(),

                'metadata' => [
                    'abonnement_code' => $abonnement->abonnement_code,
                    'abonne_id' => $abonne->id,
                    'forfait_id' => $abonnement->abonne_forfait_id,
                    'source' => 'mobile'
                ]
            ];

            $response = Http::withHeaders([
                'X-API-Key' => GeniusMarchand::getApiKey(),
                'X-API-Secret' => GeniusMarchand::getApiSecret(),
                'Accept' => 'application/json'
            ])->post(
                GeniusMarchand::getBaseUrl() . '/payments',
                $payload
            );

            if (
                !str_contains(
                    $response->header('Content-Type'),
                    'application/json'
                )
            ) {
                return [
                    'status' => false,
                    'message' => 'GeniusPay retourne une réponse invalide',
                    'body' => substr($response->body(), 0, 500)
                ];
            }

            $json = $response->json();

            Log::info('GENIUSPAY MOBILE RESPONSE', is_array($json) ? $json : ['raw' => $response->body()]);

            if (
                !isset($json['success']) ||
                !$json['success']
            ) {
                return [
                    'status' => false,
                    'message' => $json['error']['message']
                        ?? $json['message']
                        ?? 'Erreur GeniusPay'
                ];
            }

            return [
                'status' => true,
                'payment_url' =>
                    $json['data']['checkout_url']
                    ?? $json['data']['payment_url'],
                'reference' => $json['data']['reference']
            ];

        } catch (\Throwable $th) {

            Log::error('GENIUSPAY MOBILE ERROR', [
                'message' => $th->getMessage()
            ]);

            return [
                'status' => false,
                'message' => $th->getMessage()
            ];
        }
    }

    /**
     * Vérifie le paiement GeniusPay et active l'abonnement mobile.
     */
    public function confirmGeniusPayMobilePayment(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Non authentifié',
                ], 401);
            }

            $reference = $request->input('reference');
            if (empty($reference)) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Référence de paiement obligatoire',
                ], 400);
            }

            $abonne = AbonnesMobileModels::where('user_id', $user->id)->first();
            if (!$abonne) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Abonné non trouvé',
                ], 404);
            }

            $abonnement = AbonnementsMobileModels::where('payment_reference', $reference)
                ->where('abonne_id', $abonne->id)
                ->first();

            if (!$abonnement) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Abonnement introuvable pour cette référence',
                ], 404);
            }

            if ((int) $abonnement->payments === 1) {
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Abonnement déjà activé',
                    'already_paid' => true,
                ]);
            }

            $webviewSuccess = filter_var(
                $request->input('webview_success', false),
                FILTER_VALIDATE_BOOLEAN
            );
            $isSandbox = str_starts_with((string) GeniusMarchand::getApiKey(), 'pk_sandbox_')
                || str_starts_with((string) $reference, 'SANDBOX_');

            $json = null;
            $paymentStatus = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = Http::withHeaders([
                    'X-API-Key' => GeniusMarchand::getApiKey(),
                    'X-API-Secret' => GeniusMarchand::getApiSecret(),
                    'Accept' => 'application/json',
                ])->get(GeniusMarchand::getBaseUrl() . '/payments/' . $reference);

                $json = $response->json();
                Log::info('GENIUSPAY CONFIRM MOBILE', [
                    'attempt' => $attempt,
                    'body' => is_array($json) ? $json : ['raw' => $response->body()],
                ]);

                $paymentStatus = $json['data']['status']
                    ?? $json['status']
                    ?? null;

                if (in_array($paymentStatus, ['completed', 'success', 'paid'], true)) {
                    break;
                }

                if ($attempt < 3) {
                    usleep(700000);
                }
            }

            $verifiedByApi = in_array($paymentStatus, ['completed', 'success', 'paid'], true);
            $sandboxFallback = !$verifiedByApi
                && $isSandbox
                && $webviewSuccess
                && (
                    ($json['error']['code'] ?? null) === 'TRANSACTION_NOT_FOUND'
                    || $paymentStatus === null
                );

            if (!$verifiedByApi && !$sandboxFallback) {
                return response()->json([
                    'status' => 'error',
                    'code' => 402,
                    'message' => 'Paiement non confirmé auprès de GeniusPay',
                    'payment_status' => $paymentStatus,
                    'geniuspay' => $json,
                ], 402);
            }

            if ($sandboxFallback) {
                Log::warning('GENIUSPAY SANDBOX CONFIRM WITHOUT API STATUS', [
                    'reference' => $reference,
                    'abonne_id' => $abonne->id,
                    'abonnement_code' => $abonnement->abonnement_code,
                ]);
            }

            $amount = $json['data']['amount'] ?? $abonnement->montant_abonnements;
            $paymentMethod = $json['data']['payment_method']
                ?? ($sandboxFallback ? 'GeniusPay Sandbox' : 'GeniusPay');
            $description = $json['data']['description'] ?? 'Abonnement mobile Alerte Info';

            DB::beginTransaction();
            try {
                $abonnement->payments = 1;
                $abonnement->payment_reference = $reference;
                $abonnement->save();

                DB::table('abonnes_mobile_models')
                    ->where('id', $abonne->id)
                    ->update([
                        'status_abonnement' => 1,
                        'updated_at' => Carbon::now(),
                    ]);

                $existingTx = TransactionsModels::where('transaction_id', $reference)->first();
                if (!$existingTx) {
                    $transaction = new TransactionsModels();
                    $transaction->transaction_id = $reference;
                    $transaction->montant = $amount;
                    $transaction->operations = $description;
                    $transaction->method_payment = is_string($paymentMethod) ? $paymentMethod : 'GeniusPay';
                    $transaction->date_transaction = Carbon::now()->format('Y-m-d H:i:s');
                    $transaction->status = 'completed';
                    $transaction->save();
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Abonnement activé avec succès',
            ]);
        } catch (\Throwable $e) {
            Log::error('CONFIRM GENIUSPAY MOBILE ERROR', [
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
