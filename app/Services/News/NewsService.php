<?php

namespace App\Services\News;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Logs\CustomLogError;
use Illuminate\Http\Request;
use App\Models\News\NewsModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\JsonResponseServices\JsonResponseService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;
use App\Services\UploadFileManagerServices\UploadFileManagerService;



class NewsService {

    public function __construct(
        private readonly NewsModel $newsModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogError,
        private readonly UploadFileManagerService $uploadFileManagerService,
        private readonly CodeGeneratorService $codeGeneratorService,
    ) {
    }

    public function srv_getNews(Request $requestData): JsonResponse
    {
        try {
            // Récupérer les paramètres de pagination
            $page = $requestData->input('page', 1); // Page actuelle (par défaut : 1)
            $perPage = $requestData->input('per_page', 20); // Nombre d'éléments par page (par défaut : 10)

            $news = $this->newsModel->with('rubrique', 'rubriqueCategory')
            //->withTrashed()
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

            $newsDataFormated = $news->getCollection()->map(function ($news) {
                return [
                    "news_title" => $news->news_title,
                    "news_author" => $news->news_author,
                    "news_views" => $news->news_views,
                    "rubrique" => $news->rubrique->rubrique_name ?? null,
                    "categorie" => $news->rubriqueCategory->rubrique_categorie_name ?? null,
                    "published" => $news->published,
                    "news_slug" => $news->news_slug,
                    "created_at" => $news->created_at,
                    "updated_at" => $news->updated_at,
                ];
            });



            return $this->jsonResponseService->srv_successResponseWithData(
                "News récupérées avec succès",
                [
                    "news" => $newsDataFormated,
                    "pagination" => [
                        "total" => $news->total(),
                        "per_page" => $news->perPage(),
                        "current_page" => $news->currentPage(),
                        "last_page" => $news->lastPage(),
                        "from" => $news->firstItem(),
                        "to" => $news->lastItem(),
                    ]
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération des news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getRecentNews(): JsonResponse
    {
        try {
            $news = $this->newsModel
            ->with('rubrique', 'rubriqueCategory')
            ->limit(31)
            ->orderByDesc('id')
            ->get()
            ->map(function ($news) {
                return [
                    "news_title" => $news->news_title,
                    "news_author" => $news->news_author,
                    "news_views" => $news->news_views,
                    "rubrique" => $news->rubrique->rubrique_name ?? null,
                    "categorie" => $news->rubriqueCategory->rubrique_categorie_name ?? null,
                    "published" => $news->published,
                    "news_slug" => $news->news_slug,
                    "created_at" => $news->created_at,
                    "updated_at" => $news->updated_at,
                ];
            });

            // get total news and news published or not
            $totalNews = $this->newsModel->count();
            $publishedNews = $this->newsModel->where('published', 'Publié')->count();
            $unpublishedNews = $this->newsModel->where('published', 'Brouillon')->count();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Articles récupérés avec succès",
                [
                    "news" => $news,
                    "totalNews" => $totalNews,
                    "publishedNews" => $publishedNews,
                    "unpublishedNews" => $unpublishedNews,
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération des news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_getNewsLimited(): JsonResponse
    {
        try {
            $news = $this->newsModel->limit(20)->orderByDesc('id')->get();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Articles récupérés avec succès",
                $news,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération des news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // get news by slug
    public function srv_getNewsBySlug($slug): JsonResponse
    {
        try {
            $news = $this->newsModel->with('rubrique', 'rubriqueCategory')
            ->where('news_slug', $slug)->first();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Article récupéré avec succès",
                $news,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération de l'article",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération de l'article",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_createNews(Request $requestData): JsonResponse
    {
        DB::beginTransaction();
        try {

            $userData = null;

            if(auth('admin')->check()) {
                $userData = auth('admin')->user();
            }else{
                return $this->jsonResponseService->srv_errorResponse(
                    "Une erreur est survenue lors de la création de la news",
                    Response::HTTP_UNAUTHORIZED
                );
            }


            $codeUnique = $this->codeGeneratorService->generateDefaultCodeUnique(
                "news_models",
                "news_code_unique",
                "NEWS-"
            );
            $createdAt = "";
            $newsPublishedAt = $requestData->published_at;
            if(isset($newsPublishedAt)){
                $createdAt = Carbon::parse($newsPublishedAt)->format('Y-m-d H:i:s');
            }else{
                $createdAt = now()->format('Y-m-d H:i:s');
            }

            $news = $this->newsModel->create([
                "news_code_unique" => $codeUnique,
                "news_title" => $requestData->news_title,
                "news_lead" => $requestData->news_lead,
                "news_content" => $requestData->news_content,
                "media_path" => $requestData->news_media_path,
                "media_legend" => $requestData->media_legend,
                "news_author" => $requestData->news_author ?? $userData->first_name . " " . $userData->last_name,
                "news_views" => $requestData->news_views,
                "rubrique_code_unique" => $requestData->rubrique_code_unique,
                "rubrique_category_code_unique" => $requestData->rubrique_categorie_code_unique,
                "news_slug" => Str::slug(now()->format('Ymdis'). '-' . $requestData->news_title),
                "created_at" => $createdAt,
            ]);

            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Article créé avec succès",
                $news->news_slug,
                Response::HTTP_CREATED,
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                "Une erreur est survenue lors de la création de la news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la création de la news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateNews(Request $requestData, $slug): JsonResponse
    {
        DB::beginTransaction();
        try {
            $news = $this->newsModel->where('news_slug', $slug)->firstOrFail();

            $slug = null;
            $oldNewsTitle = $news->news_title;
            $newNewsTitle = $requestData->news_title;

            $newSlug = Str::slug(now()->format('Ymdis'). '-' . $newNewsTitle);

            if ($oldNewsTitle !== $newNewsTitle) {
                $slug = $newSlug;
            }else{
                $slug = $news->news_slug;
            }

            $createdAt = "";
            $newsPublishedAt = $requestData->published_at;
            if(isset($newsPublishedAt)){
                $createdAt = Carbon::parse($newsPublishedAt)->format('Y-m-d H:i:s');
            }else{
                $createdAt = now()->format('Y-m-d H:i:s');
            }

            $userData = null;

            if(auth('admin')->check()) {
                $userData = auth('admin')->user();
            }else{
                return $this->jsonResponseService->srv_errorResponse(
                    "Une erreur est survenue lors de la création de la news",
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $news->update([
                "news_title" => $requestData->news_title,
                "news_lead" => $requestData->news_lead,
                "news_content" => $requestData->news_content,
                "media_path" => $requestData->news_media_path,
                "media_legend" => $requestData->media_legend,
                "news_author" => $requestData->news_author ?? $userData->first_name . " " . $userData->last_name,
                "rubrique_code_unique" => $requestData->rubrique_code_unique,
                "rubrique_category_code_unique" => $requestData->rubrique_categorie_code_unique,
                "news_slug" => $slug,
                "created_at" => $createdAt,
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Article mise à jour avec succès",
                $news->news_slug,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->customLogError->logError(
                "Une erreur est survenue lors de la mise à jour de la news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour de la news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteNews($slug): JsonResponse
    {
        try {

            if(empty($slug)){
                return $this->jsonResponseService->srv_errorResponse(
                    "Une erreur est survenue lors de la suppression de la news",
                    Response::HTTP_BAD_REQUEST
                );
            }

            $news = $this->newsModel->where('news_slug', $slug)->firstOrFail();
            $news->delete();
            return $this->jsonResponseService->srv_successResponse(
                "Article supprimé avec succès",
                Response::HTTP_OK,
            );
        }catch(ModelNotFoundException $mth){
            $this->customLogError->logError(
                "Une erreur est survenue lors de la suppression de la news",
                $mth
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression de la news",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la suppression de la news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression de la news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_updateNewsState($slug, $state): JsonResponse
    {
        DB::beginTransaction();
        try {
            $news = $this->newsModel->where('news_slug', $slug)->firstOrFail();
            $news->update([
                "published" => $state,
            ]);
            Log::info("News mise à jour avec succès", [
                "slug" => $slug,
                "state" => $state,
            ]);
            DB::commit();
            return $this->jsonResponseService->srv_successResponseWithData(
                "News mise à jour avec succès",
                $news,
                Response::HTTP_OK,
            );
        }catch(ModelNotFoundException $mth){
            $this->customLogError->logError(
                "Une erreur est survenue lors de la mise à jour de la news",
                $mth
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour de la news",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la mise à jour de la news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la mise à jour de la news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_restoreNews($slug): JsonResponse
    {
        try {
            $news = $this->newsModel->withTrashed()->where('slug', $slug)->firstOrFail();
            $news->restore();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Article restauré avec succès",
                $news,
                Response::HTTP_OK,
            );
        }catch(ModelNotFoundException $mth){
            $this->customLogError->logError(
                "Une erreur est survenue lors de la restauration de la news",
                $mth
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la restauration de la news",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la restauration de la news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la restauration de la news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function srv_deleteNewsPermanently($slug): JsonResponse
    {
        try {
            $news = $this->newsModel->withTrashed()->where('slug', $slug)->firstOrFail();
            $news->forceDelete();
            return $this->jsonResponseService->srv_successResponseWithData(
                "Article supprimé avec succès",
                $news,
                Response::HTTP_OK,
            );
        }catch(ModelNotFoundException $mth){
            $this->customLogError->logError(
                "Une erreur est survenue lors de la suppression de la news",
                $mth
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression de la news",
                Response::HTTP_NOT_FOUND
            );
        }
        catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la suppression de la news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la suppression de la news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }



    // get rapport hebdomadaire
    public function srv_getRapportHebdomadaire(Request $request): JsonResponse
    {
        $date_debut = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->subDays(6)->startOfDay();
        $date_fin = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();

        if($date_debut == null && $date_fin == null):
            // Date de début = aujourd'hui - 6 jours
            $date_debut = now()->subDays(6)->startOfDay();
            $date_fin = now()->endOfDay();
        endif;

        try {
            // count news by author
            $journalisteArticle = $this->newsModel
            ->whereBetween('created_at', [$date_debut, $date_fin])
            ->where('published', 'Publié')
            ->selectRaw('news_author, COUNT(*) as total')
            ->groupBy('news_author')
            ->get()
            ->map(function ($item) {
                return [
                    'journaliste' => $item->news_author,
                    'total' => $item->total,
                ];
            })
            ->toArray();


            // count news by rubrique
            $rubriqueArticle = $this->newsModel
            ->join('news_rubrique_models', 'news_models.rubrique_code_unique', '=', 'news_rubrique_models.rubrique_code_unique')
            ->whereBetween('news_models.created_at', [$date_debut, $date_fin])
            ->where('news_models.published', 'Publié')
            ->selectRaw('news_rubrique_models.rubrique_name, COUNT(*) as total')
            ->groupBy('news_rubrique_models.rubrique_name')
            ->orderBy('total', 'desc') // pour voir les plus gros groupes
            ->get()
            ->map(function ($item) {
                return [
                    'rubrique' => $item->rubrique_name,
                    'total' => (int) $item->total, // cast en int au cas où
                ];
            })
            ->toArray();

            return $this->jsonResponseService->srv_successResponseWithData(
                "Rapport hebdomadaire récupéré avec succès",
                [
                    "journalisteArticle" => $journalisteArticle,
                    "rubriqueArticle" => $rubriqueArticle,
                ],
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération du rapport hebdomadaire",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération du rapport hebdomadaire",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }



    public function srv_filterInNewsDate(Request $request): JsonResponse
    {
        $date_debut = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->subDays(6)->startOfDay();
        $date_fin = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();

        $newsRubrque = $request->rubrique_code_unique; // is array
        $newsCategorie = $request->rubrique_categorie_code_unique; // is array

        try {
            $query = $this->newsModel
            ->with('rubrique', 'rubriqueCategory')
            ->where('published', 'Publié');


            // 3. Appliquer les filtres conditionnellement
            if ($date_debut && $date_fin) {
                $query->whereBetween('created_at', [$date_debut, $date_fin]);
            }

            if (!empty($newsRubrque)) {
                $query->whereIn('rubrique_code_unique', (array) $newsRubrque);
            }

            if (!empty($newsCategorie)) {
                $query->whereIn('rubrique_categorie_code_unique', (array) $newsCategorie);
            }



            $news = $query->get()->map(function ($news) {
                return [
                    "news_title" => $news->news_title,
                    "news_author" => $news->news_author,
                    "news_views" => $news->news_views,
                    "rubrique" => $news->rubrique->rubrique_name ?? null,
                    "categorie" => $news->rubriqueCategory->rubrique_categorie_name ?? null,
                    "published" => $news->published,
                    "news_slug" => $news->news_slug,
                    "created_at" => $news->created_at,
                    "updated_at" => $news->updated_at,
                ];
            });

            return $this->jsonResponseService->srv_successResponseWithData(
                "Articles récupérés avec succès",
                $news,
                Response::HTTP_OK,
            );
        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération des news",
                $th
            );
            return $this->jsonResponseService->srv_errorResponse(
                "Une erreur est survenue lors de la récupération des news",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}

