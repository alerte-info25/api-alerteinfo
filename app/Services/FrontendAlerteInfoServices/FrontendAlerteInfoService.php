<?php

namespace App\Services\FrontendAlerteInfoServices;

use Carbon\Carbon;
use Google\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\WebFcm\WebFcmTokenModels;
use App\Models\AbonnementsWebModels\AbonnementWebModels;

class FrontendAlerteInfoService
{
    public static function get_default_alerteinfo_home_page_data()
    {
        try {

            $now = now(); 
            // get all news tilte at the weekend
            $weekend_news_data = DB::table('depeche_models')
            ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
            ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
            ->select( 'rubrique_models.rubrique','countries_models.pays','countries_models.flag','depeche_models.titre',
                'depeche_models.slug', 'depeche_models.created_at', 'depeche_models.counter','depeche_models.media_url'
            )
            ->where('depeche_models.status',1)
            ->where('depeche_models.created_at', '>=', Carbon::now()->subDays(7))
            ->orderByDesc('depeche_models.id')
            ->limit(10)
            ->get();

            $depecheCountry = DB::table('depeche_models')->select('pays_id')->pluck('pays_id')->unique();
            $newsCountry = DB::table('countries_models')->whereIn('id',$depecheCountry)->get();

            $africa_news_data = DB::table('depeche_models')
            ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
            ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
            ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select('countries_models.pays','countries_models.flag','rubrique_models.rubrique','genre_journalistique_models.genre','depeche_models.*')
            //->whereDate('depeche_models.created_at','>=', Carbon::now()->subDay())
            ->where('depeche_models.status',1)
            ->orderBy('depeche_models.id', 'desc')
            ->limit(9)
            ->get();

            // get news subDays -1
            $open_access_news = DB::table('depeche_models')
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
            ->where('depeche_models.created_at', '<=', $now->copy()->subHours(24))
            ->where('depeche_models.created_at', '>', $now->copy()->subHours(48))
            ->orderByDesc('depeche_models.created_at')
            ->limit(4)
            ->get();



            // get news subDays -2 (archives)
            $archives_data = DB::table('depeche_models')
            ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
            ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
            ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select('countries_models.pays','countries_models.flag','rubrique_models.rubrique','genre_journalistique_models.genre','depeche_models.*')
            ->where('depeche_models.created_at', '<', $now->copy()->subHours(48))
            ->where('depeche_models.status',1)
            ->orderByDesc('depeche_models.id')
            ->limit(7)
            ->get();

            // get rubrique list
            $rubrique_list_data = DB::table('rubrique_models')->select('rubrique','slug')->get();


            
            $video_data = DB::table('media_models')->orderBy('id', 'desc')->limit(4)->get();


            $banners = DB::table('banner_models')
            ->select('libelle', 'banner_image', 'img_url', 'id') // limiter les champs si besoin
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('plateform', 'all')
                    ->orWhere('plateform', 'alerteinfo');
            })
            ->whereIn('libelle', ['728X90', '1920X309', '1200X1500'])
            ->get()
            ->groupBy('libelle'); // regrouper par libellé

            // Extraire chaque bannière
            $banner_728X90 = $banners->get('728X90')?->first();
            $banner_1920X309 = $banners->get('1920X309')?->first();
            $banner_1200X1500 = $banners->get('1200X1500')?->first();

            return response()->json([
                'weekend_news_data' => $weekend_news_data,
                'africa_news_data' => $africa_news_data,
                'open_access_news' => $open_access_news,
                'archives_data' => $archives_data,
                'rubrique_data' => $rubrique_list_data,
                'video_data' => $video_data,
                'banner_728X90' => $banner_728X90,
                'banner_1920X309' => $banner_1920X309,
                'banner_1200X1500' => $banner_1200X1500,
                'news_country' => $newsCountry,
            ], 200);

        

        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de la page d\'accueil. Veuillez réessayer plus tard.',
            ], 500);
        }
    }
    public static function getNewsDetails($slug)
    {
        (int) $old_counter = DB::table('depeche_models')->where('slug', $slug)->value('counter');

        $new_counter = $old_counter + 1;

        DB::table('depeche_models')->where('slug', $slug)->update(['counter' => $new_counter]);

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
        ->where('depeche_models.slug', $slug)
        ->where('depeche_models.status', 1)
        ->first();
    }

    
    // get abonne alerte info home page data
    public static function getAbonneAlerteinfoHomePageData($country_id)
    {
        try {
            // get all news tilte at the weekend
            $weekend_news_data = DB::table('depeche_models')
            ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
            ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
            ->select( 'rubrique_models.rubrique','countries_models.pays','countries_models.flag','depeche_models.titre',
                'depeche_models.slug', 'depeche_models.created_at', 'depeche_models.counter','depeche_models.media_url'
            )
            ->where('depeche_models.status',1)
            ->where('depeche_models.created_at', '>=', Carbon::now()->subDays(7))
            ->where('depeche_models.pays_id', $country_id)
            ->orderByDesc('depeche_models.id')
            ->limit(10)
            ->get();

            $depecheCountry = DB::table('depeche_models')->select('pays_id')->pluck('pays_id')->unique();
            $newsCountry = DB::table('countries_models')->whereIn('id',$depecheCountry)->get();



            $africa_news_data = DB::table('depeche_models')
            ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
            ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
            ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select('countries_models.pays','countries_models.flag','rubrique_models.rubrique','genre_journalistique_models.genre','depeche_models.*')
            ->where('depeche_models.status',1)
            ->where('depeche_models.pays_id', $country_id)
            ->orderBy('depeche_models.id', 'desc')
            ->limit(9)
            ->get();

            // get news subDays -1
            $open_access_news = DB::table('depeche_models')
            ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
            ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
            ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select('countries_models.pays','countries_models.flag','rubrique_models.rubrique','genre_journalistique_models.genre','depeche_models.*')
            ->whereDate('depeche_models.created_at', Carbon::now()->subDays(1)->toDateString())
            ->where('depeche_models.status',1)
            ->where('depeche_models.pays_id', $country_id)
            ->orderByDesc('depeche_models.id')
            ->limit(4)
            ->get();



            // get news subDays -2 (archives)
            $archives_data = DB::table('depeche_models')
            ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
            ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
            ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select('countries_models.pays','countries_models.flag','rubrique_models.rubrique','genre_journalistique_models.genre','depeche_models.*')
            ->whereDate('depeche_models.created_at','<', Carbon::now()->subDays(1)->toDateString())
            ->where('depeche_models.status',1)
            ->where('depeche_models.pays_id', $country_id)
            ->orderByDesc('depeche_models.id')
            ->limit(7)
            ->get();

            // get rubrique list
            $rubrique_list_data = DB::table('rubrique_models')->select('rubrique','slug')->get();


            $video_data = DB::table('media_models')->orderBy('id', 'desc')->limit(4)->get();

            $banner_728X90 = DB::table('banner_models')->where('libelle',"728X90")->where('status', 1)->orderByDesc('id')->first();
            $banner_1920X309 = DB::table('banner_models')->where('libelle',"1920X309")->where('status', 1)->orderByDesc('id')->first();
            $banner_1200X1500 = DB::table('banner_models')->where('libelle',"1200X1500")->where('status', 1)->orderByDesc('id')->first();

            return response()->json([
                'weekend_news_data' => $weekend_news_data,
                'africa_news_data' => $africa_news_data,
                'open_access_news' => $open_access_news,
                'archives_data' => $archives_data,
                'rubrique_list_data' => $rubrique_list_data,
                'video_data' => $video_data,
                'banner_728X90' => $banner_728X90,
                'banner_1920X309' => $banner_1920X309,
                'banner_1200X1500' => $banner_1200X1500,
                'news_country' => $newsCountry,
            ], 200);

        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de la page d\'accueil. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    ////get_alerteinfo_depeche_open_access_data
    public static function getAlerteinfoDepecheOpenAccessData()
    {
        try {
            $now = now(); // Heure actuelle

            // Sélection des dépêches gratuites (open access)
            $article = DB::table('depeche_models')
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
                ->whereRaw('? BETWEEN DATE_ADD(depeche_models.created_at, INTERVAL 24 HOUR) AND DATE_ADD(depeche_models.created_at, INTERVAL 48 HOUR)', [$now])
                ->orderByDesc('depeche_models.created_at')
                ->get();

            // Liste des rubriques
            $rubrique_list_data = DB::table('rubrique_models')
                ->select('rubrique', 'slug')
                ->get();

            return [
                'article' => $article,
                'rubrique_data' => $rubrique_list_data,
            ];
        } catch (\Throwable $th) {
            Log::error("Erreur lors de la récupération des dépêches open access : " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => "Une erreur est survenue lors de la récupération des dépêches open access. Veuillez réessayer plus tard.",
            ], 500);
        }
    }



    //get_alerteinfo_depeche_archives_data
    public static function getAlerteinfoDepecheArchivesData()
    {
        try {
            $article = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'genre_journalistique_models.genre', 'depeche_models.*')
                ->where('depeche_models.status', 1)
                ->whereDate('depeche_models.created_at', '<', Carbon::now()->subDays(1)->toDateString())
                ->orderByDesc('depeche_models.id')
                ->limit(100)
                ->get();

            // get rubrique list
            $rubrique_list_data = DB::table('rubrique_models')->select('rubrique', 'slug')->get();

            // older news
            $older_news = DB::table('depeche_models')
                ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
                ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubrique_models.rubrique', 'genre_journalistique_models.genre', 'depeche_models.*')
                ->where('depeche_models.status', 1)
                ->whereDate('depeche_models.created_at', '<', Carbon::now()->subDays(7)->toDateString())
                ->orderByDesc('depeche_models.id')
                ->limit(10)
                ->get();

            return [
                'article' => $article,
                'rubrique_data' => $rubrique_list_data,
                'older_news' => $older_news,
            ];
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de la page d\'accueil. Veuillez réessayer plus tard.',
            ], 500);
        }
    }





    // check if abonne have an subscription
    public static function checkIfSubscriberHaveSubscription()
    {
        try {
            
            $account_code_unique = auth('abonne')->user()->account_code_unique;
            //return $account_code_unique;
            // Récupération des abonnements valides en une seule requête
            $abonnementsActifs = DB::table('abonnement_web_models')
            ->join('abonnement_web_countrie_models', 'abonnement_web_models.abonnement_web_code', '=', 'abonnement_web_countrie_models.abonnement_web_code')
            ->where('abonnement_web_models.account_code_unique', $account_code_unique)
            ->where('abonnement_web_models.payments', 1) // Filtre sur les abonnements payés
            ->whereDate('abonnement_web_models.end_date', '>=', now()) // Filtre sur les abonnements actifs
            ->pluck('abonnement_web_countrie_models.country_id') // On récupère uniquement les `country_id`
            ->unique() // Évite les doublons
            ->values()
            ->toArray();

            // Vérification si l'utilisateur a des abonnements actifs
            if (empty($abonnementsActifs)) {
                return [
                    'message' => "noActiveSubscription"
                ];
            }


            
            return $abonnementsActifs;

        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la vérification de l\'abonné. Veuillez réessayer plus tard.' .$th->getMessage(),
            ], 500);
        }
    }






    public static function checkSubscriberSubscriptionData($account_code_unique)
    {
        try {
            return DB::table('abonnement_web_models')
            ->where('account_code_unique', $account_code_unique)
            ->where('payments', 1) // Filtre sur les abonnements payés
            ->whereDate('end_date', '>=', now()) // Filtre sur les abonnements actifs
            ->get();
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de l\'abonné. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    // check if user subscriptions is paid
    private static function checkIfUserSubscribedIsPaid($account_code_unique)
    {
        return DB::table('abonnement_web_models')
        ->where('account_code_unique', $account_code_unique)
        ->where('payments', 1)
        ->exists();
    }

    // check if user subscriptions is expired
    private static function checkIfUserSubscribedIsExpired($account_code_unique)
    {
        return DB::table('abonnement_web_models')
        ->where('account_code_unique', $account_code_unique)
        ->whereDate('end_date', '>=', now())
        ->exists();
    }

    // get user subscriptions countries
    private static function getUserSubscribedCountries($account_code_unique)
    {
        return DB::table('abonnement_web_models')
        ->where('account_code_unique', $account_code_unique)
        ->where('payments', 1) // Seulement les abonnements payés
        ->whereDate('end_date', '>=', now()) // Seulement les abonnements encore valides
        ->pluck('country_id') // Récupère uniquement les country_id
        ->unique() // Supprime les doublons si nécessaire
        ->values(); // Réindexe proprement
    }


    // filter in depeche 
    //getAlerteinfoWebDepecheFilteredData
    public function getAlerteinfoWebDepecheFilteredData($query)
    {
        try {
            //code...
            return DB::table('depeche_models')
            ->join('countries_models', 'depeche_models.pays_id', '=', 'countries_models.id')
            ->join('rubrique_models', 'depeche_models.rubrique_id', '=', 'rubrique_models.id')
            ->select('countries_models.flag','depeche_models.titre','depeche_models.lead',
                'depeche_models.slug', 'depeche_models.created_at', 'depeche_models.counter'
            )
            ->where('depeche_models.status',1)
            ->where(function ($newsQuery)use ($query)  {
                $newsQuery->where('depeche_models.titre', 'LIKE', '%' . $query . '%')
                    ->orWhere('depeche_models.lead', 'LIKE', '%' . $query . '%');
            })
            ->orderByDesc('depeche_models.id')
            ->limit(50)
            ->get();

        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de l\'abonné. Veuillez réessayer plus tard.',
            ], 500);
        }
    }


    //getAlerteinfoWebAbonneDashboardData
    public function getAlerteinfoWebAbonneDashboardData($account_code_unique)
    {
        try {
            //code...
            // Récupérer les abonnements avec les relations nécessaires
                $subscriptionData = AbonnementWebModels::with([
                    'countrie.country:id,pays', // Charge uniquement les colonnes nécessaires pour les pays
                    'forfaits:id,forfait',           // Charge directement le forfait associé
                    'transactions:id,transaction_id,montant,method_payment,date_transaction,operations,status' // Charge les transactions associées
                ])
                ->where('account_code_unique', $account_code_unique) // Filtrer par account_code_unique
                ->get(['id', 'start_date', 'end_date', 'payments', 'slug', 'forfait_id','abonnement_web_code']); // Sélectionne uniquement les champs nécessaires

            // Transformer les données pour ne retourner que les informations demandées
            $formattedData = $subscriptionData->map(function ($abonnement) {
                return [
                    'start_date' => $abonnement->start_date,
                    'end_date' => $abonnement->end_date,
                    'payments' => $abonnement->payments,
                    'slug' => $abonnement->slug,
                    'countries' => $abonnement->countrie && !$abonnement->countrie->isEmpty()
                        ? $abonnement->countrie->pluck('country.pays')->unique()->values() // Noms des pays
                        : [],
                    'forfait' => $abonnement->forfaits ? $abonnement->forfaits->forfait : null, // Nom du forfait
                    'transactions' => $abonnement->transactions ? $abonnement->transactions : [], // Transactions
                ];
            });
            $abonneData = DB::table('abonnes_web_models')
            ->join('categories_abonnes_web_models', 'abonnes_web_models.category_code', '=', 'categories_abonnes_web_models.category_code')
            ->select(
                'abonnes_web_models.full_name',
                'abonnes_web_models.email',
                'abonnes_web_models.phone',
                'categories_abonnes_web_models.categorie'
            )
            ->where('abonnes_web_models.account_code_unique', $account_code_unique)
            ->first();


            // Retourner une réponse JSON avec les données récupérées
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Abonnements récupérés avec succès.',
                'data' => $formattedData,
                'abonne' => $abonneData,
            ], 200);
            
        }catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la récupération des données: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des données de l\'abonné. Veuillez réessayer plus tard.'. $th->getMessage(),
            ], 500);
        }
    }

    // store fcm_token if not exist
    public function storeFcmToken($fcm_token)
    {
        try {
            // Utiliser upsert pour insérer ou ignorer les doublons
            DB::table('web_fcm_token_models')->upsert(
                ['tokens' => $fcm_token, 'created_at' => now(), 'updated_at' => now()],
                ['tokens'],
                ['updated_at']
            );
            return response()->json([
                'status' => 'Succès',
                'code' => 200,
                'message' => 'Fcm token ajouté avec succès.',
            ], 200);

        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la création du token: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la création du token. Veuillez réessayer plus tard.'. $th->getMessage(),
            ], 500);
        }

    }

    //deleteFcmToken
    public function deleteFcmToken($fcm_token)
    {
        try {
           // Supprimer le token et vérifier le nombre de lignes affectées
            $deletedRows = DB::table('web_fcm_token_models')->where('tokens', $fcm_token)->delete();

            if ($deletedRows > 0) {
                return response()->json([
                    'status' => 'Succès',
                    'code' => 200,
                    'message' => 'FCM token supprimé avec succès.',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'Erreur',
                    'code' => 404,
                    'message' => 'FCM token non trouvé.',
                ], 404);
            }
        } catch (\Throwable $th) {
            // Log
            Log::error("Erreur lors de la création du token: ". $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'Erreur',
                'code' => 400,
                'message' => 'Fcm token non existant.',
            ], 400);
        }
    }



    public function getOAuthToken()
    {
        // Chemin vers votre fichier JSON
        $serviceAccountKeyPath = storage_path('app/firebase/alerte-info-web-push-firebase-adminsdk-fbsvc-9db5f84a14.json');

        try {
            $client = new Client();
            $client->setAuthConfig($serviceAccountKeyPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->fetchAccessTokenWithAssertion();

            return $client->getAccessToken()['access_token'];
        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération du jeton OAuth 2.0 : " . $e->getMessage());
            return null;
        }
    }

}

