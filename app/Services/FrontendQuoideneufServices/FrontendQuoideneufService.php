<?php

namespace App\Services\FrontendQuoideneufServices;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PigisteModel\PigisteModel;
use App\Services\MailSenderServices\MailSenderService;

class FrontendQuoideneufService
{
    public function srv_getQuoideneufHomeData()
    {
        DB::beginTransaction();
        try {

            // get all news tilte at the weekend
            $weekend_news_data = DB::table('articles_models')
                ->select('articles_models.titre', 'articles_models.slug', 'articles_models.created_at')
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->limit(10)->get();

            //$depecheCountry = DB::table('articles_models')->select('pays_id')->pluck('pays_id')->unique();


            $une_news_data = DB::table('articles_models')
                ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubriques_quoideneuf_models.rubrique', 'genre_journalistique_models.genre', 'articles_models.*')
                ->whereMonth('articles_models.created_at', Carbon::now()->month)
                ->whereYear('articles_models.created_at', Carbon::now()->year)
                ->where('articles_models.status', 1)
                ->orderBy('articles_models.id', 'desc')
                ->limit(30)
                ->get();


            // popular news
            $popular_news_data = DB::table('articles_models')
                ->select('titre', 'counter', 'articles_models.slug', 'articles_models.created_at', 'articles_models.media_url')
                ->whereMonth('articles_models.created_at', Carbon::now()->month)
                ->whereYear('articles_models.created_at', Carbon::now()->year)
                ->where('counter', '>', 100)
                ->where('status', 1)
                ->orderByDesc('counter')
                ->limit(20)
                ->get();


            // get rubrique list
            $rubrique_list_data = DB::table('rubriques_quoideneuf_models')->select('rubrique', 'slug')->get();

            $banners = DB::table('banner_models')
            ->select('libelle', 'banner_image', 'img_url', 'id') // limiter les champs si besoin
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('plateform', 'all')
                    ->orWhere('plateform', 'quoideneuf');
            })
            ->whereIn('libelle', ['728X90', '1920X309'])
            ->get()
            ->groupBy('libelle'); // regrouper par libellé

            // Extraire chaque bannière
            $banner_728X90 = $banners->get('728X90')?->first();
            $banner_1920X309 = $banners->get('1920X309')?->first();

            DB::commit();
            return response()->json([
                'weekend_news_data' => $weekend_news_data,
                'une_news_data' => $une_news_data,
                'popular_news_data' => $popular_news_data,
                'rubrique_data' => $rubrique_list_data,
                'banner_728X90' => $banner_728X90,
                'banner_1920X309' => $banner_1920X309,
            ], 200);


        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la récupération des données: " . $th->getMessage(), [
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

    public function srv_getNewsDetails($slug): JsonResponse
    {
        DB::beginTransaction();
        try {

            (int) $old_counter = DB::table('articles_models')->where('slug', $slug)->value('counter');

            $new_counter = $old_counter + 1;

            DB::table('articles_models')->where('slug', $slug)->update(['counter' => $new_counter]);


            $news_details_data = DB::table('articles_models')
                ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubriques_quoideneuf_models.rubrique', 'genre_journalistique_models.genre', 'articles_models.*')
                ->where('articles_models.slug', $slug)
                ->where('articles_models.status', 1)
                ->first();


            // get rubrique list
            $rubrique_list_data = DB::table('rubriques_quoideneuf_models')->select('rubrique', 'slug')->get();

            $similar_news_data = DB::table('articles_models')
                ->select('titre', 'rubrique_id', 'counter', 'slug', 'created_at', 'media_url')
                ->where('rubrique_id', $news_details_data->rubrique_id)
                ->where('status', 1)
                ->where('id', '!=', $news_details_data->id)
                ->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()])
                ->orderByDesc('counter')
                ->limit(15)
                ->get();


            DB::commit();
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'news_details_data' => $news_details_data,
                'rubrique_list_data' => $rubrique_list_data,
                'similar_news_data' => $similar_news_data,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la récupération du détail de l'actualité: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération du détail de l\'actualité. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    // get similar news
    public function srv_getSimilarNews(array $rubrique_id)
    {
        DB::beginTransaction();
        try {
            $similar_news_data = DB::table('articles_models')
                ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubriques_quoideneuf_models.rubrique', 'genre_journalistique_models.genre', 'articles_models.*')
                ->whereIn('articles_models.rubrique_id', $rubrique_id)
                ->where('articles_models.status', 1)
                ->orderBy('articles_models.id', 'desc')
                ->limit(5)
                ->get();

            DB::commit();
            return $similar_news_data;
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la récupération des actualités similaires: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des actualités similaires. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    public function srv_getCountryList(): JsonResponse
    {
        DB::beginTransaction();
        try {
            $country_list_data = DB::table('countries_models')->select('pays', 'flag', 'id')->get();
            DB::commit();
            return response()->json([
                'country_list_data' => $country_list_data,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la récupération de la liste des pays: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération de la liste des pays. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    public function srv_getNewsByRubrique($rubrique_slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $news_by_rubrique_data = DB::table('articles_models')
                ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select('countries_models.pays', 'countries_models.flag', 'rubriques_quoideneuf_models.rubrique', 'genre_journalistique_models.genre', 'articles_models.*')
                ->where('articles_models.status', 1)
                ->where('rubriques_quoideneuf_models.slug', $rubrique_slug)
                ->orderBy('articles_models.id', 'desc')
                ->limit(100)
                ->get();

            $rubrique_list_data = DB::table('rubriques_quoideneuf_models')->select('rubrique', 'slug')->get();

            $popular_news_data = DB::table('articles_models')
                ->select('titre', 'counter', 'articles_models.slug', 'articles_models.created_at', 'articles_models.media_url')
                ->whereMonth('articles_models.created_at', Carbon::now()->month)
                ->whereYear('articles_models.created_at', Carbon::now()->year)
                ->where('counter', '>', 100)
                ->where('status', 1)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();


            $banners = DB::table('banner_models')
            ->select('libelle', 'banner_image', 'img_url', 'id') // limiter les champs si besoin
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('plateform', 'all')
                    ->orWhere('plateform', 'quoideneuf');
            })
            ->whereIn('libelle', ['728X90', '1920X309'])
            ->get()
            ->groupBy('libelle'); // regrouper par libellé

            // Extraire chaque bannière
            $banner_728X90 = $banners->get('728X90')?->first();
            $banner_1920X309 = $banners->get('1920X309')?->first();

            DB::commit();
            return response()->json([
                'news_data' => $news_by_rubrique_data,
                'rubrique_list_data' => $rubrique_list_data,
                'popular_news_data' => $popular_news_data,
                'banner_728X90' => $banner_728X90,
                'banner_1920X309' => $banner_1920X309,
                'code' => 200,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la récupération du détail de l'actualité: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération du détail de l\'actualité. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    public function srv_getVideo(): JsonResponse
    {
        DB::beginTransaction();
        try {
            $video_data = DB::table('media_models')->orderBy('id', 'desc')->limit(50)->get();

            $rubrique_list_data = DB::table('rubriques_quoideneuf_models')->select('rubrique', 'slug')->get();
            // popular news
            $popular_news_data = DB::table('articles_models')
                ->select('titre', 'counter', 'articles_models.slug', 'articles_models.created_at', 'articles_models.media_url')
                ->whereMonth('articles_models.created_at', Carbon::now()->month)
                ->whereYear('articles_models.created_at', Carbon::now()->year)
                ->where('counter', '>', 100)
                ->where('status', 1)
                ->orderByDesc('created_at')
                //->limit(9)
                ->get();

            $banners = DB::table('banner_models')
            ->select('libelle', 'banner_image', 'img_url', 'id') // limiter les champs si besoin
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('plateform', 'all')
                    ->orWhere('plateform', 'quoideneuf');
            })
            ->whereIn('libelle', ['728X90', '1920X309'])
            ->get()
            ->groupBy('libelle'); // regrouper par libellé

            // Extraire chaque bannière
            $banner_728X90 = $banners->get('728X90')?->first();
            $banner_1920X309 = $banners->get('1920X309')?->first();



            DB::commit();
            return response()->json([
                'video_data' => $video_data,
                'rubrique_list_data' => $rubrique_list_data,
                'popular_news_data' => $popular_news_data,
                'banner_728X90' => $banner_728X90,
                'banner_1920X309' => $banner_1920X309,
                'code' => 200,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la récupération du détail de l'actualité: ", [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération du détail de l\'actualité. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    public function srv_getPopularNews(Request $request): JsonResponse
    {
        try {
            // Paramètres avec valeurs par défaut
            $year = (int) $request->input('year', Carbon::now()->year);
            $month = (int) $request->input('month', Carbon::now()->month);
            $query = trim($request->input('query', '')); // Supprimer les espaces inutiles

            $queryBuilder = DB::table('articles_models')
                ->select('id', 'titre', 'lead', 'slug', 'counter', 'created_at', 'media_url')
                ->where('status', 1)
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->where('counter', '>', 50)
                ->orderByDesc('counter');

            // ✅ Ajouter la recherche par mot-clé si fourni
            if (!empty($query)) {
                $queryBuilder->where(function ($q) use ($query) {
                    $q->where('titre', 'LIKE', "%{$query}%")
                        ->orWhere('lead', 'LIKE', "%{$query}%");
                });
            }

            $popular_news_data = $queryBuilder->get();

            return response()->json([
                'popular_news_data' => $popular_news_data,
                'code' => 200,
            ], 200);

        } catch (\Throwable $th) {
            Log::error("Erreur lors de la récupération des actualités populaires: ", [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des actualités populaires. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    // get archive
    public function srv_getArchive(Request $request): JsonResponse
    {
        try {
            // Paramètres avec valeurs par défaut
            $year = $request->input('year', Carbon::now()->year);
            $month = $request->input('month', Carbon::now()->month);
            $query = $request->input('query', ''); // Par défaut vide

            // --- Archive Data ---
            $archiveQuery = DB::table('articles_models')
                ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'countries_models.pays',
                    'countries_models.flag',
                    'rubriques_quoideneuf_models.rubrique',
                    'genre_journalistique_models.genre',
                    'articles_models.*'
                )
                ->whereYear('articles_models.created_at', $year)
                ->whereMonth('articles_models.created_at', $month)
                ->where('articles_models.status', 1); // Publié

            // 🔍 Appliquer la recherche si `query` est fourni
            if (!empty($query)) {
                $archiveQuery->where(function ($q) use ($query) {
                    $q->where('articles_models.titre', 'LIKE', "%{$query}%")
                        ->orWhere('articles_models.contenus', 'LIKE', "%{$query}%")
                        ->orWhere('countries_models.pays', 'LIKE', "%{$query}%")
                        ->orWhere('rubriques_quoideneuf_models.rubrique', 'LIKE', "%{$query}%")
                        ->orWhere('genre_journalistique_models.genre', 'LIKE', "%{$query}%");
                });
            }

            $archive_data = $archiveQuery
                ->orderBy('articles_models.id', 'desc')
                ->limit(50)
                ->get();

            // --- Popular News Data ---
            $popular_news_data = DB::table('articles_models')
                ->select('titre', 'counter', 'articles_models.slug', 'articles_models.created_at', 'articles_models.media_url')
                ->whereYear('articles_models.created_at', $year)
                ->whereMonth('articles_models.created_at', $month)
                ->where('articles_models.status', 1)
                ->where(function ($q) {
                    $q->where('counter', '>', 100)  // ✅ D'abord > 100
                        ->orWhere('counter', '>', 50); // ✅ Puis > 50 (si >100 vide)
                })
                ->orderByDesc('counter')
                ->limit(9)
                ->get();

            // --- Rubrique List Data ---
            $rubrique_list_data = DB::table('rubriques_quoideneuf_models')
                ->select('rubrique', 'slug')
                ->get();

            $banners = DB::table('banner_models')
            ->select('libelle', 'banner_image', 'img_url', 'id') // limiter les champs si besoin
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('plateform', 'all')
                    ->orWhere('plateform', 'quoideneuf');
            })
            ->whereIn('libelle', ['728X90', '1920X309'])
            ->get()
            ->groupBy('libelle'); // regrouper par libellé

            // Extraire chaque bannière
            $banner_728X90 = $banners->get('728X90')?->first();
            $banner_1920X309 = $banners->get('1920X309')?->first();


            return response()->json([
                'archive_data' => $archive_data,
                'popular_news_data' => $popular_news_data,
                'rubrique_data' => $rubrique_list_data,
                'banner_728X90' => $banner_728X90,
                'banner_1920X309' => $banner_1920X309,
                'code' => 200,
            ], 200);

        } catch (\Throwable $th) {
            Log::error("Erreur lors de la récupération des archives: ", [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des archives. Veuillez réessayer plus tard.',
            ], 500);
        }
    }


    // filtre dans les news

    
    public function srv_getFilterNews(Request $request): JsonResponse
    {
        try {
            // ✅ Récupérer le paramètre `query` depuis la requête
            $query = trim($request->input('query', ''));

            $queryBuilder = DB::table('articles_models')
                ->select('id', 'titre', 'lead', 'slug', 'counter', 'created_at', 'media_url')
                ->where('status', 1)
                ->orderByDesc('created_at')
                ->limit(25);

            // ✅ Ajouter la recherche par mot-clé si fourni
            if (!empty($query)) {
                $queryBuilder->where(function ($q) use ($query) {
                    $q->where('titre', 'LIKE', "%{$query}%")
                        ->orWhere('lead', 'LIKE', "%{$query}%");
                });
            }

            $filter_news_data = $queryBuilder->get();

            return response()->json([
                'filter_news_data' => $filter_news_data,
                'code' => 200,
            ], 200);

        } catch (\Throwable $th) {
            Log::error("Erreur lors de la récupération des actualités filtrées: ", [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des actualités filtrées. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    public function srv_createPigiste(Request $request)
    {
        DB::beginTransaction();
        try {

            // upload cv to public storage directory and return path
            $isUpload = $request->file('pigiste_cv')->store('documents/pigiste_cv', 'public');

            $pigisteCreated = PigisteModel::create([
                'uuid' => Str::uuid(),
                'pigiste_first_name' => $request->pigiste_first_name,
                'pigiste_last_name' => $request->pigiste_last_name,
                'pigiste_email' => $request->pigiste_email,
                'pigiste_phone' => $request->pigiste_phone,
                'pigiste_address' => $request->pigiste_address,
                'pigiste_country' => $request->pigiste_country,
                'pigiste_speciality' => $request->pigiste_speciality,
                'pigiste_accept_terms' => $request->pigiste_accept_terms,
                'pigiste_cv' => $isUpload ? $isUpload : null,
                'pigiste_comment' => $request->pigiste_comment,
            ]);

            if($pigisteCreated){
                // send email notification to admin

                $mailIsSending = MailSenderService::srv_sendPigisteFormNotification($pigisteCreated);

                // Log de l'échec de l'envoi de l'email
                if (!$mailIsSending) {
                    Log::warning('Échec de l\'envoi de l\'email de notification', [
                        'email' => $request->pigiste_email,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Votre demande a bien été soumise avec succès. Vous serez contacté très prochainement pour la suite',
                'code' => 200,
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Erreur lors de la création du pigiste: ", [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la création du pigiste. Veuillez réessayer plus tard.',
            ], 500);
        }
    } 

    public function srv_getRubriqueList(): JsonResponse
    {
        try {
            $rubrique_list_data = DB::table('rubriques_quoideneuf_models')->select('rubrique', 'slug')->get();
            return response()->json([
                'rubrique_list_data' => $rubrique_list_data,
                'code' => 200,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la récupération de la liste des rubriques: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération de la liste des rubriques. Veuillez réessayer plus tard.',
            ], 500);
        }
    }

    // get banner banner_1200X1500
    public function srv_getBanner1200X1500(): JsonResponse
    {
        try {
            $banner_1200X1500 = DB::table('banner_models')
                ->select('libelle', 'banner_image', 'img_url', 'id')
                ->where('status', 1)
                ->where(function ($query) {
                    $query->where('plateform', 'all')
                        ->orWhere('plateform', 'quoideneuf');
                })
                ->whereIn('libelle', ['1200X1500'])
                ->get()
                ->groupBy('libelle');
            
            $banner_1200X1500 = $banner_1200X1500->get('1200X1500')?->first();
            return response()->json([
                'banner_1200X1500' => $banner_1200X1500,
                'code' => 200,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log
            Log::error("Erreur lors de la récupération de la bannière: " . $th->getMessage(), [
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            // return error response
            return response()->json([
                'status' => 'erreur',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la récupération de la bannière. Veuillez réessayer plus tard.',
            ], 500);
        }
    }
}



