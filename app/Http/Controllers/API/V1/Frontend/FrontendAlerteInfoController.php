<?php

namespace App\Http\Controllers\API\V1\Frontend;

use DateTime;
use Exception;
use DateInterval;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Mail\SendAccountMailer;
use App\Services\CodeGenerator;
use Illuminate\Support\Facades\DB;
use App\Services\CinetPay\CinetPay;
use App\Services\CinetPay\Marchand;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\WebFcm\WebFcmTokenModels;
use App\Models\Redactions\RubriqueModels;
use App\Models\AbonnesWebModels\AbonnesWebModels;
use App\Models\AbonnementsWebModels\AbonnementWebModels;
use App\Services\FrontendAlerteInfoServices\FrontendAlerteInfoService;
use App\Services\GeniusPay\GeniusMarchand;
class FrontendAlerteInfoController extends Controller
{
    protected $frontendAlerteInfoService;
    public function __construct(FrontendAlerteInfoService $frontendAlerteInfoService)
    {
        $this->frontendAlerteInfoService = $frontendAlerteInfoService;
    }
    public function get_alerteinfo_home_page_data($account_code_unique)
    {
        try {
            return $this->frontendAlerteInfoService::get_default_alerteinfo_home_page_data();

            // Vérifier si la fonction a retourné un message d'erreur
            if (is_array($getUserSubscribedCountries) && isset($getUserSubscribedCountries['message'])) {
            }
            // Retourner les données de l'abonné si des abonnements actifs existent
            return $this->frontendAlerteInfoService::getAbonneAlerteinfoHomePageData($getUserSubscribedCountries);
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




    // get depeche detail

    public function get_alerteinfo_news_details($slug)
    {
        try {

            $news_details_data = $this->frontendAlerteInfoService::getNewsDetails($slug);



            $arry = [$news_details_data->rubrique_id];

            $similar_news_data = DB::table('depeche_models')
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
                ->where('depeche_models.status', 1)
                ->whereIn('depeche_models.rubrique_id', $arry)
                ->where('depeche_models.slug', '!=', $slug)
                ->orderBy('depeche_models.id', 'desc')
                ->limit(6)
                ->get();


            // get rubrique list
            $rubrique_list_data = DB::table('rubrique_models')
                ->select('rubrique', 'slug')
                ->get();

            // update message value with : Cette dépêche est réservée aux abonnés de l'actualité de @flag @country_name and inject the country name of current news Automatically
            $message = "Cette dépêche est réservée à nos abonnés   <img width='35' height='20' src='" . $news_details_data->flag . "' /> " ;
            //style="width: 35px !important; height: 20px !important"



            $abonneCategoryInfo = '';
            // check auth
            $isAuth = auth('abonne')->check();
            // check abonne category info
            if($isAuth == true) {
                $user = auth('abonne')->user();

                //Log::info("User : [". $user ."]");
                $abonneCategoryInfo = DB::table('categories_abonnes_web_models')
                    ->select('categories_abonnes_web_models.can_copy','categories_abonnes_web_models.can_download')
                    ->where('categories_abonnes_web_models.category_code', $user->category_code)
                    ->first();

                    Log::info("Category Info : @", [$abonneCategoryInfo]);
                    //return response()->json($abonneCategoryInfo);
            }

            Log::info("Abonne Category Info : @", [$abonneCategoryInfo]);



            // check auth
            if (!$isAuth) {
                $isDataChecked = $this->checkNewsCreatedAt($news_details_data->created_at);

                if ($isDataChecked == true) {
                    return response()->json([
                        'news_details_data' => $news_details_data,
                        'similar_news_data' => $similar_news_data,
                        'rubrique_data' => $rubrique_list_data,
                        'newsCanBeRead' => true,
                        'subscriptionCountriesNotAuthorized' => true,
                        'abonneCategoryInfo' => $abonneCategoryInfo ?? '',
                        'message' => ""
                    ]);
                } else {
                    return response()->json([
                        'news_details_data' => $news_details_data,
                        'similar_news_data' => $similar_news_data,
                        'rubrique_data' => $rubrique_list_data,
                        'newsCanBeRead' => false,
                        'subscriptionCountriesNotAuthorized' => false,
                        'abonneCategoryInfo' => $abonneCategoryInfo ?? '',
                        'message' => $message,
                    ]);
                }
            }
            // Récupérer les pays des abonnements actifs
            $getUserSubscribedCountries = $this->frontendAlerteInfoService::checkIfSubscriberHaveSubscription();

            //return [gettype($getUserSubscribedCountries)] ;


            // Vérifier si la fonction a retourné un message d'erreur
            if (is_array($getUserSubscribedCountries) && isset($getUserSubscribedCountries['message'])) {
                return response()->json([
                    'news_details_data' => $news_details_data,
                    'similar_news_data' => $similar_news_data,
                    'rubrique_data' => $rubrique_list_data,
                    'newsCanBeRead' => false,
                    'abonneCategoryInfo' => $abonneCategoryInfo ?? '',
                    'subscriptionCountriesNotAuthorized' => false,
                    'message' => $message,

                ]);
            }


            //return is_array($getUserSubscribedCountries);

            //return in_array((int) $news_details_data->pays_id, $getUserSubscribedCountries);
            if (in_array((int) $news_details_data->pays_id, $getUserSubscribedCountries)) {
                return response()->json([
                    'news_details_data' => $news_details_data,
                    'similar_news_data' => $similar_news_data,
                    'rubrique_data' => $rubrique_list_data,
                    'newsCanBeRead' => true,
                    'subscriptionCountriesNotAuthorized' => true,
                    'abonneCategoryInfo' => $abonneCategoryInfo ?? '',
                    'message' => ""
                ]);
            } else {
                return response()->json([
                    'news_details_data' => $news_details_data,
                    'similar_news_data' => $similar_news_data,
                    'rubrique_data' => $rubrique_list_data,
                    'newsCanBeRead' => false,
                    'subscriptionCountriesNotAuthorized' => false,
                    'abonneCategoryInfo' => $abonneCategoryInfo ?? '',
                    'message' => $message,
                ]);
            }

        } catch (\Throwable $e) {

            Log::error("Erreur lors de la récupération de l'article: ", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(
                [
                    'status' => 'Erreur',
                    'code' => 500,
                    'message' => "Erreur lors de la récupération de l'article",
                ]
            );
        }

    }


    private function checkNewsCreatedAt($created_at)
    {
        $now = Carbon::now();
        $createdAt = Carbon::parse($created_at);

        // Vérifie : now >= created_at + 24h  ET  now < created_at + 48h
        return $now->gte($createdAt->copy()->addHours(24))
            && $now->lt($createdAt->copy()->addHours(48));
    }

    private function checkIfNewsCoutryIdIsIncluded($country, $countriesId)
    {
        return in_array($country, $countriesId);
    }


    public function get_alerteinfo_depeche_archives_data_by_mounth_and_year($mounth, $year)
    {
        try {
            return DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'genre_journalistique_models.genre', 'depeche_models.*')
                ->whereMonth('depeche_models.created_at', $mounth)
                ->where('depeche_models.status', 1)
                ->whereYear('depeche_models.created_at', $year)
                ->orderByDesc('depeche_models.created_at')
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

    public function get_alerteinfo_depeche_by_rubrique($rubrique_slug)
    {
        try {
            // Get depeche by rubrique slug
            $rubrique_id = DB::table('rubrique_models')->where('slug', $rubrique_slug)->pluck('id');

            $article = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'genre_journalistique_models.genre', 'depeche_models.*')

                ->where('rubrique_models.slug', $rubrique_slug)
                ->where('depeche_models.status', 1)
                ->orderByDesc('depeche_models.id')
                ->limit(100)
                ->get();


            // Get similar rubrique
            $similar_news_data = DB::table('depeche_models')
                ->orderByDesc('counter')
                ->whereIn('depeche_models.rubrique_id', $rubrique_id)
                ->where('status', 1)
                ->limit(9)
                ->get();

            // get rubrique list
            $rubrique_list_data = DB::table('rubrique_models')->select('rubrique', 'slug')->get();

            $categorie = RubriqueModels::where('slug', $rubrique_slug)
                ->value('rubrique');

            return [
                'article' => $article,
                'similar_news_data' => $similar_news_data,
                'rubrique_data' => $rubrique_list_data,
                'categorie' => $categorie,
            ];


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

    //get_alerteinfo_depeche_open_access_data
    public function get_alerteinfo_depeche_open_access_data()
    {
        try {
            return $this->frontendAlerteInfoService::getAlerteinfoDepecheOpenAccessData();
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
    //get_alerteinfo_depeche_archives_data
    public function get_alerteinfo_depeche_archives_data()
    {
        try {
            return $this->frontendAlerteInfoService::getAlerteinfoDepecheArchivesData();
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



    // get web abonnement categorie
    public function get_alerteinfo_web_abonnement_categorie()
    {
        try {
            return DB::table('categories_abonnes_web_models')
                ->select('categorie', 'category_code', 'can_copy', 'can_share', 'can_read', 'can_download')
                ->get();

        } catch (\Throwable $e) {
            // Log
            Log::error("Erreur lors de la recupération des données Catégorie: " . $e->getMessage());

            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => "Erreur lors de la recupération des données: ",
                ]
            );

        }
    }


    // get_alerteinfo_web_about_data

    public function get_alerteinfo_web_about_data()
    {
        try {
            return DB::table('abouts_models')
                ->select('contents')
                ->first();
        } catch (\Throwable $e) {
            // Log
            Log::error("Erreur lors de la récupération des données 'À propos': " . $e->getMessage());

            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => "Erreur lors de la récupération des données 'À propos'.",
                ]
            );
        }
    }

    //our_contacts_models

    //get_alerteinfo_web_our_contacts_data
    public function get_alerteinfo_web_our_contacts_data()
    {
        try {
            return DB::table('our_contacts_models')
                ->select('contents')
                ->first();
        } catch (\Throwable $e) {
            // Log
            Log::error("Erreur lors de la récupération des données de contact: " . $e->getMessage());

            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => "Erreur lors de la récupération des données de contact.",
                ]
            );
        }
    }

    //get_alerteinfo_web_our_reference_data
    public function get_alerteinfo_web_our_reference_data()
    {
        try {
            return DB::table('our_references_models')
                ->select('contents')
                ->first();
        } catch (\Throwable $e) {
            // Log
            Log::error("Erreur lors de la récupération des données de contact: " . $e->getMessage());

            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => "Erreur lors de la récupération des données de contact.",
                ]
            );
        }
    }

    //get_alerteinfo_web_our_blog_data
    public function get_alerteinfo_web_our_blog_data()
    {
        try {
            return DB::table('our_blogs_models')
                ->select('title','lead', 'media_path', 'slug', 'created_at', 'updated_at')
                ->get();
        } catch (\Throwable $e) {
            // Log
            Log::error("Erreur lors de la récupération des données de contact: " . $e->getMessage());

            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => "Erreur lors de la récupération des données de contact.",
                ]
            );
        }
    }


    // get alerteinfo_web_blog_detail
    public function get_alerteinfo_web_blog_detail($slug)
    {
        try {
            return DB::table('our_blogs_models')
                ->select('title','lead','contents', 'media_path', 'slug', 'created_at', 'updated_at')
                ->where('slug', $slug)
                ->first();
        } catch (\Throwable $e) {
            // Log
            Log::error("Erreur lors de la récupération des données de contact: " . $e->getMessage());

            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => "Erreur lors de la récupération des données de contact.",
                ]
            );
        }
    }

    //get_alerteinfo_web_our_services_data
    public function get_alerteinfo_web_our_services_data()
    {
        try {
            return DB::table('our_services_models')
                ->select('title','contents', 'media_path', 'slug', 'created_at', 'updated_at')
                ->get();
        } catch (\Throwable $e) {
            // Log
            Log::error("Erreur lors de la récupération des données de contact: " . $e->getMessage());

            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => "Erreur lors de la récupération des données de contact.",
                ], 500
            );
        }
    }



    // get web abonnement form data
    public function get_alerteinfo_web_abonnement_form_data($abonneSlug)
    {
        try {
            // get current web abonne data
            $current_abonne = DB::table('abonnes_web_models')
                ->join('categories_abonnes_web_models', 'abonnes_web_models.category_code', '=', 'categories_abonnes_web_models.category_code')
                ->select(
                    'categories_abonnes_web_models.category_code',
                    'abonnes_web_models.account_code_unique',
                    'abonnes_web_models.full_name',
                    'categories_abonnes_web_models.categorie'
                )
                ->where('abonnes_web_models.slug', $abonneSlug)->first();

            if ($current_abonne != null) {

                $newsCountry = DB::table('depeche_models')->select('pays_id')->pluck('pays_id')->unique();
                $fulCountrie = DB::table('countries_models')->whereIn('id', $newsCountry)->get();

                $forfaits = DB::table('abonnement_web_forfaits_models')->where('status', 'PREMIUM')
                    ->where('category_code', $current_abonne->category_code)
                    ->get();

                return [
                    'forfaits' => $forfaits,
                    'abonne' => $current_abonne,
                    'full_countrie' => $fulCountrie,
                ];
            } else {
                return response()->json([
                    'forfaits' => [],
                    'abonne' => [],
                    'full_countrie' => [],
                ]);
            }

        } catch (\Throwable $e) {
            // Log
            Log::error("Erreur lors de la recupération des données: abonnement_form_data : " . $e->getMessage());

            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => "Erreur lors de la recupération des données: ",
                ]
            );
        }
    }

    // store abonne data to database
    public function store_alerteinfo_web_abonne_data(Request $request)
    {
        //return $request->all();
        try {
            // validate
            if (empty($request->category_code)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Le code de catégorie est obligatoire',
                ]);
            }
            if (empty($request->full_name)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Le nom complet est obligatoire',
                ]);
            }
            if (empty($request->email)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'L\'adresse email est obligatoire',
                ]);
            }
            // check if email adresse is valid
            if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'L\'adresse email est invalide',
                ]);
            }
            if (empty($request->phone)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Le numéro de téléphone est obligatoire',
                ]);
            }

            // check if user email exists in abonnes_web_models
            $abonne_exists = DB::table('abonnes_web_models')->where('email', $request->email)->first();
            if ($abonne_exists) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'L\'adresse email est déjà utilisé par un abonné .',
                ]);
            }

            // gererate password
            $password = strtoupper(str_shuffle(Str::random(5) . rand(10000, 99999)));

            // store data in abonnes_web_models
            $store_abonne = new AbonnesWebModels();
            $store_abonne->account_code_unique = Carbon::now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
            $store_abonne->category_code = $request->category_code;
            $store_abonne->full_name = $request->full_name;
            $store_abonne->email = $request->email;
            $store_abonne->phone = $request->phone;

            $store_abonne->password = password_hash($password, PASSWORD_BCRYPT);
            $store_abonne->status = 1;
            $store_abonne->slug = Str::lower(Str::random(40));

            if ($store_abonne->save()) {
                // send email with password
                $subject_email = "ALERTE INFO WEB: NOTIFICATION APRES CREATION DE COMPTE";
                $default_text = "L'agence de presse vous remercie pour la création de votre compte. Voici vos identifiants pour avoir accès à votre espace.";

                // Envoi de l'email de confirmation
                try {
                    Mail::to($request->email)->send(new SendAccountMailer(
                        $password,
                        $store_abonne->full_name,
                        $store_abonne->email,
                        $subject_email,
                        $default_text
                    ));
                } catch (Exception $e) {
                    Log::error("Erreur lors de l'envoi de l'email de confirmation: " . $e->getMessage());
                }

                return response()->json([
                    'status' => 'Succès',
                    'code' => 200,
                    'slug' => $store_abonne->slug,
                    'message' => 'Votre compte a été créé avec succès. Un email vous a été envoyé avec vos accès. Vous pouvez associer un abonnement à votre compte.',
                ]);
            }


        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la création de l'abonné: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            // return error response
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => 'Une erreur est survenue lors de la création de l\'abonné.',
                ]
            );

        }
    }





    //checkSubscriberSubscriptionData
    public function checkSubscriberSubscriptionData($account_code_unique)
    {
        try {
            return $this->frontendAlerteInfoService::checkSubscriberSubscriptionData($account_code_unique);
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


    //get_alerteinfo_web_depeche_filtered_data
    public function getAlerteinfoWebDepecheFilteredData($account_code_unique, $query)
    {
        try {
            // Récupérer les pays des abonnements actifs
            return $this->frontendAlerteInfoService->getAlerteinfoWebDepecheFilteredData($query);
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données filtrées: " . $th->getMessage());
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => "Erreur lors de la récupération des données filtrées",
                ],
                500
            );
        }
    }


    //get_alerteinfo_web_abonne_dashboard_data
    public function getAlerteinfoWebAbonneDashboardData($account_code_unique)
    {
        try {
            return $this->frontendAlerteInfoService->getAlerteinfoWebAbonneDashboardData($account_code_unique);
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données du tableau de bord de l'abonné: " . $th->getMessage());
            return response()->json(
                [
                    'status' => 'error',
                    'code' => 500,
                    'message' => "Erreur lors de la récupération des données du tableau de bord de l'abonné",
                ]
            );
        }
    }




    // create abonne abonnement
    public function store_alerteinfo_web_abonnement_data(Request $request)
    {
        DB::beginTransaction();
        try {
            // validate
            if (empty($request->account_code_unique)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Le code unique de l\'abonné est obligatoire svp',
                ]);
            }
            if (empty($request->forfait_id)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Le forfait est obligatoire',
                ]);
            }
            if (sizeof($request->country_id) == 0) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Veuillez choisi au moins un pays.',
                ]);
            }

            // Récupérer les informations du forfait
            $forfait_info = DB::table('abonnement_web_forfaits_models')->where('id', $request->forfait_id)->first();
            if (!$forfait_info) {
                return response()->json(['code' => 404, 'status' => 'erreur', 'message' => "Erreur! Forfait non trouvé"]);
            }

            // Calculer la date de fin
            $sizeOfCountry = sizeof($request->country_id);
            $dateline = 'P' . $forfait_info->duree . 'D';
            $date_fin = new DateTime();
            $date_fin->add(new DateInterval($dateline));
            $date_fin_formatted = $date_fin->format('Y-m-d H:i:s');

            // store data to database
            $store_abonnement = new AbonnementWebModels();
            $store_abonnement->abonnement_web_code = Carbon::now()->format('YmdHis') . '-' . Str::upper(Str::random(8));
            $store_abonnement->account_code_unique = $request->account_code_unique;
            $store_abonnement->forfait_id = $request->forfait_id;
            $store_abonnement->montant = (int) $sizeOfCountry * $forfait_info->montant;
            $store_abonnement->start_date = Carbon::now()->format('Y-m-d H:i:s');
            $store_abonnement->end_date = $date_fin_formatted;

            $store_abonnement->country_code = $request->customer_country;
            $store_abonnement->customer_address = $request->customer_address;
            $store_abonnement->customer_zip_code = $request->customer_zip_code;
            $store_abonnement->customer_city = $request->customer_city;
            $store_abonnement->customer_state = $request->customer_state;

            $store_abonnement->slug = CodeGenerator::generateSlugCode();

            if (!$store_abonnement->save()) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Erreur lors de la création de l\'abonnement',
                ]);
            }


            //store abonnement countrie
            if (sizeof($request->country_id) > 0) {
                foreach ($request->country_id as $country) {
                    DB::table('abonnement_web_countrie_models')->insert([
                        'abonnement_web_code' => $store_abonnement->abonnement_web_code,
                        'country_id' => $country,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
            DB::commit();

            // get abonne data by account_code_unique join categorie
            $abonne_data = DB::table('abonnes_web_models')
                ->join('categories_abonnes_web_models', 'abonnes_web_models.category_code', '=', 'categories_abonnes_web_models.category_code')
                ->select(
                    'categories_abonnes_web_models.category_code',
                    'abonnes_web_models.account_code_unique',
                    'abonnes_web_models.full_name',
                    'abonnes_web_models.email',
                    'abonnes_web_models.phone',
                    'categories_abonnes_web_models.categorie'
                )
                ->where('abonnes_web_models.account_code_unique', $request->account_code_unique)
                ->first();

            if ($abonne_data == null) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Erreur! Abonné non trouvé',
                ]);
            }

            $description = "PAIEMENT ABONNEMENT ALERTE INFO WEB @ " . $abonne_data->categorie;

            $url = $this->__cinetpayAbonnementAction($store_abonnement, $abonne_data, $description);
            if ($url != null) {
                // redirection vers l'url de paiement
                return response()->json([
                    'status' => 'Succès',
                    'code' => 200,
                    'paymentLink' => $url,
                    'message' => 'L\'abonnement a été créé avec succès.',
                ]);
            } else {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Erreur lors de la création de l\'abonnement',
                ]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la création de l'abonnement pour l'abonné: " . $th->getMessage());

            // return error response
            return response()->json(
                [
                    'status' => 'erreur',
                    'code' => 500,
                    'message' => 'Une erreur est survenue lors de la création de l\'abonnement.',
                ]
            );

        }
    }

    // store alerte info web app fcm tokens
    public function storeAlerteInfoWebappFcmTokens(Request $request)
    {
        return $this->frontendAlerteInfoService->storeFcmToken($request->fcm_tokens);
    }

    //deleteAlerteInfoWebappFcmTokens
    public function deleteAlerteInfoWebappFcmTokens(Request $request)
    {
        return $this->frontendAlerteInfoService->deleteFcmToken($request->fcm_tokens);
    }



    public function sendFCMNotification(Request $requestData)
    {
        $tokens = WebFcmTokenModels::pluck('tokens')->flatten()->toArray();

        if (empty($tokens)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun token FCM disponible.',
            ], 400);
        }

        $accessToken = $this->frontendAlerteInfoService->getOAuthToken();
        if (!$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Échec de la génération du jeton OAuth 2.0.',
            ], 500);
        }

        $requestData = [
            'titre' => $requestData->titre,
            'media_url' => $requestData->media_url,
            'slug' => $requestData->slug,
        ];

        foreach ($tokens as $token) {
            $this->sendFCMPushNotification($token, $requestData, $accessToken);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notifications FCM envoyées avec succès.',
        ], 200);
    }




    private function sendFCMPushNotification(string $token, array $requestData, string $accessToken)
    {
        try {
            $projectId = "alerte-info-web-push";

            $data = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "title" => $requestData['titre'],
                        "body" => 'Nouvel article publié : ' . $requestData['titre'],
                    ],
                    "data" => [
                        "slug" => $requestData['slug'] ?? null,
                        "article_titre" => $requestData['titre'] ?? null,
                        "article_image" => $requestData['media_url'] ?? null,
                    ],
                ],
            ];


            $dataString = json_encode($data);

            $headers = [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                Log::error("Erreur cURL : " . curl_error($ch));
                curl_close($ch);
                return;
            }

            curl_close($ch);

            $responseData = json_decode($response, true);

            if (isset($responseData['error'])) {
                Log::error("Erreur FCM : " . json_encode($responseData['error']));
            } else {
                Log::info("Notification envoyée avec succès pour le token $token");
            }

        } catch (\Throwable $th) {
            Log::error("Erreur notification : " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
        }
    }



    // cinetPay Abonnement action
    public function __cinetpayAbonnementAction($currentAbonnementData, $abonneData, $description)
    {
        try {


            //Veuillez entrer votre apiKey
            $apikey = Marchand::get_apikey();
            //Veuillez entrer votre siteId
            $site_id = Marchand::getSiteWebSiteId();

            //notify url
            $notify_url = Marchand::getSiteWebNotifyUrl();
            //return url
            $return_url = Marchand::getSiteWebUrl();
            $channels = "ALL";

            /*information supplémentaire que vous voulez afficher
            sur la facture de CinetPay(Supporte trois variables
            que vous nommez à votre convenance)*/
            $invoice_data = array(
                "Data 1" => "",
                "Data 2" => "",
                "Data 3" => ""
            );

            //
            $formData = array(
                "transaction_id" => $currentAbonnementData->abonnement_web_code,
                "amount" => $currentAbonnementData->montant,
                "currency" => "XOF",
                "customer_surname" => $abonneData->full_name,
                "customer_name" => $abonneData->full_name,
                "description" => $description,
                "notify_url" => $notify_url,
                "return_url" => $return_url,
                "channels" => $channels,
                "invoice_data" => $invoice_data,
                //pour afficher le paiement par carte de credit
                "customer_email" => $abonneData->email, //l'email du client
                "customer_phone_number" => $abonneData->phone, //Le numéro de téléphone du client
                "customer_address" => $currentAbonnementData->customer_address, //l'adresse du client
                "customer_city" => $currentAbonnementData->customer_city == null ? "ABidjan" : $currentAbonnementData->customer_city, // ville du client
                "customer_country" => $currentAbonnementData->country_code == null ? "" : $currentAbonnementData->country_code,//Le pays du client, la valeur à envoyer est le code ISO du pays (code à deux chiffre) ex : CI, BF, US, CA, FR
                "customer_state" => $currentAbonnementData->customer_state == null ? "" : $currentAbonnementData->customer_state, //L’état dans de la quel se trouve le client. Cette valeur est obligatoire si le client se trouve au États Unis d’Amérique (US) ou au Canada (CA)
                "customer_zip_code" => $currentAbonnementData->customer_zip_code == null ? "" : $currentAbonnementData->customer_zip_code  //Le code postal du client
            );

            $CinetPay = new CinetPay($site_id, $apikey, $VerifySsl = false);//$VerifySsl=true <=> Pour activerr la verification ssl sur curl
            $result = $CinetPay->generatePaymentLink($formData);

            if ($result["code"] == '201') {
                $url = $result["data"]["payment_url"];
                //dd($url);//dd($result["code"] == '201');
                // ajouter le token à la transaction enregistré
                /* $commande->update(); */
                //redirection vers l'url de paiement
                //header('Location:'.);
                return $url;

            } else {
                return null;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function getPayStatus($data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment/check',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "content-type:application/json"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err)
            print ($err);
        else
            return ($response);
    }

    //logoutAlerteInfoWeb
    public function logoutAlerteInfoWeb(Request $request)
    {
        try {
            if (auth('abonne')->check()) {
                auth('abonne')->logout();
                return response()->json([
                    'status' => 'Succès',
                    'code' => 200,
                    'message' => 'Vous êtes déconnecté avec succès',
                ]);
            } else {
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 400,
                    'message' => 'Vous n\'êtes pas connecté',
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la déconnexion',
            ]);
        }
    }

    // CinetPay v2

    // create abonne abonnement
    public function store_alerteinfo_web_abonnement_data2(Request $request)
    {
        DB::beginTransaction();
        try {
            // validation
            if (empty($request->account_code_unique)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Le code unique de l\'abonné est obligatoire 2',
                ]);
            }
            if (empty($request->forfait_id)) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Le forfait est obligatoire',
                ]);
            }
            if (!is_array($request->country_id) || count($request->country_id) === 0) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Veuillez choisir au moins un pays.',
                ]);
            }

            // Récupérer le forfait
            $forfait_info = DB::table('abonnement_web_forfaits_models')->where('id', $request->forfait_id)->first();
            if (!$forfait_info) {
                return response()->json(['code' => 404, 'status' => 'erreur', 'message' => "Erreur! Forfait non trouvé"]);
            }

            // Calculer la date de fin
            $sizeOfCountry = count($request->country_id);
            $dateline = 'P' . $forfait_info->duree . 'D';
            $date_fin = new DateTime();
            $date_fin->add(new DateInterval($dateline));

            // Stocker l'abonnement
            $store_abonnement = new AbonnementWebModels();
            $store_abonnement->abonnement_web_code = Carbon::now()->format('YmdHis') . '-' . Str::upper(Str::random(8));
            $store_abonnement->account_code_unique = $request->account_code_unique;
            $store_abonnement->forfait_id = $request->forfait_id;
            $store_abonnement->montant = (int) $sizeOfCountry * $forfait_info->montant;
            $store_abonnement->start_date = Carbon::now()->format('Y-m-d H:i:s');
            $store_abonnement->end_date = $date_fin->format('Y-m-d H:i:s');

            $store_abonnement->country_code = $request->customer_country;
            $store_abonnement->customer_address = $request->customer_address;
            $store_abonnement->customer_zip_code = $request->customer_zip_code;
            $store_abonnement->customer_city = $request->customer_city;
            $store_abonnement->customer_state = $request->customer_state;
            $store_abonnement->slug = CodeGenerator::generateSlugCode();
            if (!$store_abonnement->save()) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Erreur lors de la création de l\'abonnement',
                ]);
            }

            // store abonnement countrie
            foreach ($request->country_id as $country) {
                DB::table('abonnement_web_countrie_models')->insert([
                    'abonnement_web_code' => $store_abonnement->abonnement_web_code,
                    'country_id' => $country,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            DB::commit();

            // Récupérer les infos de l'abonné
            $abonne_data = DB::table('abonnes_web_models')
                ->join('categories_abonnes_web_models', 'abonnes_web_models.category_code', '=', 'categories_abonnes_web_models.category_code')
                ->select(
                    'categories_abonnes_web_models.category_code',
                    'abonnes_web_models.account_code_unique',
                    'abonnes_web_models.full_name',
                    'abonnes_web_models.email',
                    'abonnes_web_models.phone',
                    'categories_abonnes_web_models.categorie'
                )
                ->where('abonnes_web_models.account_code_unique', $request->account_code_unique)
                ->first();
            if (!$abonne_data) {
                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'message' => 'Erreur! Abonné non trouvé',
                ]);
            }

            $description = "PAIEMENT ABONNEMENT ALERTE INFO WEB @ " . $abonne_data->categorie;

            // Créer le paiement via CinetPay V2
            // $paymentResult = $this->__cinetpayAbonnementAction2($store_abonnement, $abonne_data, $description);

            // if ($paymentResult['status'] === true) {
            //     return response()->json([
            //         'status' => 'Succès',
            //         'code' => 200,
            //         'paymentLink' => $paymentResult['payment_url'],
            //         'message' => 'L\'abonnement a été créé avec succès.',
            //     ]);
            // }
            // Créer le paiement via GeniusPay
            $paymentResult =$this->__geniusPayAbonnementAction($store_abonnement,$abonne_data,$description);
            if ($paymentResult['status']) {

                $store_abonnement->payment_reference =
                    $paymentResult['reference'];

                $store_abonnement->save();
                return response()->json([
                    'status' => 'Succès',
                    'code' => 200,
                    'payment_provider' => 'geniuspay',
                    'paymentLink' => $paymentResult['payment_url'],
                    'message' => 'L\'abonnement a été créé avec succès.',
                ]);
            }else{

                return response()->json([
                    'status' => 'erreur',
                    'code' => 400,
                    'paymentLink' => $paymentResult['payment_url'],
                    'message' => $paymentResult['message'] ?? 'Erreur lors de la création du paiement',
                ]);
            }

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Erreur lors de la création de l'abonnement: " . $th->getMessage());
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'erreur' => $th->getMessage(),
                'message' => 'Une erreur est survenue lors de la création de l\'abonnement.',
                'line' => $th->getLine(),
                'file' => $th->getFile(),
            ]);
        }
    }

    // Crée le paiement via CinetPay V2
    public function __cinetpayAbonnementAction2($currentAbonnementData, $abonneData, $description)
    {
        try {
            $formData = [
                "merchant_transaction_id" => $currentAbonnementData->abonnement_web_code,
                "amount" => $currentAbonnementData->montant,
                "currency" => "XOF",
                "success_url" => Marchand::getSiteWebUrl(), // ou une URL spécifique de succès
                "failed_url" => Marchand::getSiteWebUrl() . "/echec", // URL de l'échec
                "designation" => $description,
                "notify_url" => Marchand::getSiteWebNotifyUrl(),
                "customer_email" => $abonneData->email ?? "noemail@example.com",
                "customer_phone_number" => $abonneData->phone ?? "0101010101",
                "customer_address" => $currentAbonnementData->customer_address ?? "Adresse non définie",
                "customer_city" => $currentAbonnementData->customer_city ?? "Abidjan",
                "customer_country" => $currentAbonnementData->country_code ?: "CI",
                "customer_state" => $currentAbonnementData->customer_state ?: "N/A",
                "customer_zip_code" => $currentAbonnementData->customer_zip_code ?: "0000",
            ];
            // dd($formData);

            $result = $this->createPaymentV2($formData);
            Log::info('CinetPay V2 Response: ', $result);

            if (isset($result['payment_url'])) {
                return [
                    'status' => true,
                    'payment_url' => $result['payment_url'],
                ];
            }

            return [
                'status' => false,
                'message' => $result['message'] ?? 'Erreur lors de la création du paiement'
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => 'Erreur CinetPay: ' . $th->getMessage(),
            ];
        }
    }
    public function createPaymentV2(array $data)
    {
        $token = $this->getValidToken();

        if (!$token) {
            return [
                'status' => false,
                'message' => 'Impossible de récupérer le token'
            ];
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.cinetpay.co/v1/payment",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return [
                'status' => false,
                'message' => $err
            ];
        }

        return json_decode($response, true);
    }


    public function getValidToken()
    {
        // Vérifier si token existe en cache
        if (Cache::has('cinetpay_token')) {
            return Cache::get('cinetpay_token');
        }

        // Génération d’un nouveau token
        $tokenData = $this->getAccesJeton();

        if (!$tokenData['status']) {
            return null;
        }

        $token = $tokenData['access_token'];
        $expiresIn = $tokenData['expires_in']; // en secondes

        // Stockage dans le cache, moins 60 secondes pour la sécurité
        Cache::put('cinetpay_token', $token, $expiresIn - 60);

        return $token;
    }

    public function getAccesJeton() {
        try {
            // Récupération des clés depuis le modèle Marchand
            $apiKey = Marchand::get_apikey2();
            $apiPassword = Marchand::get_api_password();

            // Initialisation de CURL
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.cinetpay.co/v1/oauth/login",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode([
                    "api_key" => $apiKey,
                    "api_password" => $apiPassword,
                ]),
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json"
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // <- Forcer IPv4 pour éviter le problème de whitelist
            ]);

            // Exécution de la requête
            $response = curl_exec($curl);
            $err = curl_error($curl);

            // Récupération de l'IP publique
            $ip = file_get_contents('https://api.ipify.org');
            Log::info('IP SORTIE API CINETPAY', ['ip' => $ip]);
            curl_close($curl);

            Log::info('CINETPAY FINAL TEST', [
                'api_key' => Marchand::get_apikey2(),
                'password' => Marchand::get_api_password(),
                'server_ip' => file_get_contents('https://api.ipify.org'),
            ]);

            // Vérification des erreurs CURL
            if ($err) {
                return [
                    'status' => false,
                    'message' => 'Erreur CURL: ' . $err,
                    'ip' => $ip,
                ];
            }
            Log::info('Réponse brute CinetPay OAuth', [
                'response' => $response,
                'error' => $err
            ]);

            // Décodage de la réponse
            $result = json_decode($response, true);

            if (isset($result['access_token'])) {
                return [
                    'status' => true,
                    'access_token' => $result['access_token'],
                    'expires_in' => $result['expires_in'],
                    'ip' => $ip,
                ];
            }

            // Retour si erreur API
            return [
                'status' => false,
                'message' => $result['message'] ?? 'Erreur inconnue',
                'data' => $result,
                'ip' => $ip,
            ];

        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function __geniusPayAbonnementAction(
        $currentAbonnementData,
        $abonneData,
        $description
    ) {
        try {

            $payload = [
                'amount' => (int) $currentAbonnementData->montant,
                'description' => $description,

                'customer' => [
                    'name' => $abonneData->full_name,
                    'email' => $abonneData->email,
                    'phone' => $abonneData->phone,
                    'country' => $currentAbonnementData->country_code
                ],

                // 👇 IMPORTANT pour éviter comportements bizarres
                'currency' => 'XOF',

                'success_url' => Marchand::getSiteWebUrl(),
                'error_url' => Marchand::getSiteWebUrl() . '/echec',
                'notify_url' => GeniusMarchand::getWebhookUrl(),
                'metadata' => [
                    'abonnement_web_code' => $currentAbonnementData->abonnement_web_code,
                    'account_code_unique' => $currentAbonnementData->account_code_unique,
                    'forfait_id' => $currentAbonnementData->forfait_id
                ]
            ];

            $response = Http::withHeaders([
                'X-API-Key' => GeniusMarchand::getApiKey(),
                'X-API-Secret' => GeniusMarchand::getApiSecret(),
                'Accept' => 'application/json'
            ])->post(
                'https://geniuspay.ci/api/v1/merchant/payments',
                $payload
            );
            Log::info('Payload',$payload);

            // 🔥 DEBUG IMPORTANT
            if (!str_contains($response->header('Content-Type'), 'application/json')) {
                return [
                    'status' => false,
                    'message' => 'GeniusPay retourne HTML (API invalide ou clé incorrecte)',
                    'body' => substr($response->body(), 0, 500)
                ];
            }

            $json = $response->json();

            if (!isset($json['success']) || !$json['success']) {
                return [
                    'status' => false,
                    'message' => $json['error']['message'] ?? 'Erreur GeniusPay',
                ];
            }

            return [
                'status' => true,
                'payment_url' => $json['data']['checkout_url'] ?? $json['data']['payment_url'],
                'reference' => $json['data']['reference']
            ];

        } catch (\Throwable $th) {

            Log::error('GENIUSPAY ERROR', [
                'message' => $th->getMessage()
            ]);

            return [
                'status' => false,
                'message' => $th->getMessage()
            ];
        }
    }

    // public function __geniusPayAbonnementAction(
    //     $currentAbonnementData,
    //     $abonneData,
    //     $description
    // )
    // {
    //     try {

    //         $payload = [

    //             'amount' => (int)$currentAbonnementData->montant,

    //             'description' => $description,

    //             'customer' => [
    //                 'name' => $abonneData->full_name,
    //                 'email' => $abonneData->email,
    //                 'phone' => $abonneData->phone,
    //                 'country' => $currentAbonnementData->country_code
    //             ],

    //             'success_url' => Marchand::getSiteWebUrl(),

    //             'error_url' => Marchand::getSiteWebUrl() . '/echec',

    //             'metadata' => [

    //                 'abonnement_web_code' =>
    //                     $currentAbonnementData->abonnement_web_code,

    //                 'account_code_unique' =>
    //                     $currentAbonnementData->account_code_unique,

    //                 'forfait_id' =>
    //                     $currentAbonnementData->forfait_id
    //             ]
    //         ];

    //         $response = Http::withHeaders([

    //             'X-API-Key' =>
    //                 GeniusMarchand::getApiKey(),

    //             'X-API-Secret' =>
    //                 GeniusMarchand::getApiSecret(),

    //             'Content-Type' => 'application/json'

    //         ])->post(
    //             'https://geniuspay.ci/api/v1/merchant/payments',
    //             $payload
    //         );
    //         return [
    //             'http_status' => $response->status(),
    //             'headers' => $response->headers(),
    //             'body' => $response->body(),
    //             'json' => $response->json(),
    //         ];

    //         $result = $response->json();
    //         Log::info(
    //             'GENIUSPAY CREATE PAYMENT',
    //             $result
    //         );


    //         if (
    //             isset($result['success']) &&
    //             $result['success'] === true
    //         ) {

    //             return [

    //                 'status' => true,

    //                 'payment_url' =>
    //                     $result['data']['checkout_url'],

    //                 'reference' =>
    //                     $result['data']['reference']
    //             ];
    //         }

    //         return [

    //             'status' => false,

    //             'message' =>
    //                 $result['error']['message']
    //                 ?? 'Erreur GeniusPay'
    //         ];

    //     } catch (\Throwable $th) {

    //         Log::error(
    //             'GENIUSPAY ERROR',
    //             [
    //                 'message' => $th->getMessage()
    //             ]
    //         );

    //         return [

    //             'status' => false,

    //             'message' => $th->getMessage()
    //         ];
    //     }
    // }
    public function getPaymentStatus(
        string $reference
    )
    {
        return Http::withHeaders([

            'X-API-Key' =>
                GeniusMarchand::getApiKey(),

            'X-API-Secret' =>
                GeniusMarchand::getApiSecret()

        ])->get(
            "https://geniuspay.ci/api/v1/merchant/payments/{$reference}"
        )->json();
    }



}
