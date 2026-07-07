<?php

namespace App\Http\Controllers\API\V1\Frontend;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Like\LikeModels;
use App\Models\Views\ViewsModels;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Dislike\DislikeModels;
use App\Models\Quoideneufs\MediaModels;
use App\Models\Quoideneufs\RubriquesQuoideneufModels;
use App\Services\FrontendQuoideneufServices\FrontendQuoideneufService;

class FrontendQuoideneufController extends Controller
{
    // GET FRONTEND HOME ARTICLE

    
    protected $frontendQuoideneufService;
    public function __construct(
        FrontendQuoideneufService $frontendQuoideneufService
    )
    {
        $this->frontendQuoideneufService = $frontendQuoideneufService;
    }

    
    
    public function ctrl_getQuoideneufHomeData()
    {
        return $this->frontendQuoideneufService->srv_getQuoideneufHomeData();
    }

    public function ctrl_getNewsDetails($slug)
    {
        return $this->frontendQuoideneufService->srv_getNewsDetails($slug);
    }

    public function ctrl_getCountryList()
    {
        return $this->frontendQuoideneufService->srv_getCountryList();
    }

    
    public function ctrl_getNewsByRubrique($rubriqueSlug)
    {
        return $this->frontendQuoideneufService->srv_getNewsByRubrique($rubriqueSlug);
    }

    public function ctrl_getVideo()
    {
        return $this->frontendQuoideneufService->srv_getVideo();
    }

    public function ctrl_getPopularNews(Request $request)
    {
        return $this->frontendQuoideneufService->srv_getPopularNews($request);
    }

    public function ctrl_getArchive(Request $request): JsonResponse
    {
        return $this->frontendQuoideneufService->srv_getArchive($request);
    }

    public function ctrl_getFilterNews(Request $request): JsonResponse
    {
        return $this->frontendQuoideneufService->srv_getFilterNews($request);
    }

    public function ctrl_createPigiste(Request $request): JsonResponse
    {
        return $this->frontendQuoideneufService->srv_createPigiste($request);
    }

    public function ctrl_getRubriqueList()
    {
        return $this->frontendQuoideneufService->srv_getRubriqueList();
    }

    public function ctrl_getBanner1200X1500()
    {
        return $this->frontendQuoideneufService->srv_getBanner1200X1500();
    }










    public function get_quoideneuf_home_data()
    {
        
        try {
            $une_news_data =  DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select('rubriques_quoideneuf_models.rubrique','countries_models.pays','countries_models.flag','genre_journalistique_models.genre','articles_models.*')
            ->where('articles_models.status',1)->orderBy('id', 'desc')->limit(6)->get();

            $archive_news_data = DB::table('articles_models')
            ->select(DB::raw('count(id) as `data`'), DB::raw("created_at as new_date"),  DB::raw('YEAR(created_at) year, MONTH(created_at) month'))
            ->groupby('year','month')->limit(20)->orderByDesc('created_at')->get();

            $politique_news_data = DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
            ->select('rubriques_quoideneuf_models.rubrique','countries_models.pays','countries_models.flag','genre_journalistique_models.genre','articles_models.*')
            ->where('rubriques_quoideneuf_models.rubrique', 'POLITIQUE')->where('articles_models.status', 1)
            ->orderBy('articles_models.id','desc')->limit(6)->get();

            $economie_news_data = DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
            ->select('rubriques_quoideneuf_models.rubrique','countries_models.pays','countries_models.flag','genre_journalistique_models.genre','articles_models.*')
            ->where('rubriques_quoideneuf_models.rubrique', 'ECONOMIE')->where('articles_models.status', 1)->orderBy('articles_models.id','desc')
            ->limit(4)->get();

            $popular_news_data = DB::table('articles_models')->orderByDesc('counter')->where('status', 1)->limit(9)->get();

            $video_data = DB::table('media_models')->orderBy('id', 'desc')->limit(4)->get();

            $event_keywords_data = DB::table('events_key_words_models')->where('status', 0)->get();

            


            $news_rubrique = RubriquesQuoideneufModels::whereIn('rubrique', ['INTERNATIONAL','SPORT','TRIBUNE LIBRE','SOCIETE','CULTURE'])
            ->orderBy('rubrique','asc')
            ->get();

            $other_news_rubrique = RubriquesQuoideneufModels::whereNotIn('rubrique', ['INTERNATIONAL','SPORT','TRIBUNE LIBRE','SOCIETE','CULTURE'])
            ->orderBy('rubrique','asc')
            ->get();

            // get all news tilte at the weekend
            $weekend_news_data = DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
            ->select( 'rubriques_quoideneuf_models.rubrique','countries_models.pays','countries_models.flag','articles_models.titre',
                'articles_models.slug', 'articles_models.created_at', 'articles_models.counter'
            )
            ->where('articles_models.status',1)
            ->where('articles_models.created_at', '>=', Carbon::now()->subDays(7))->orderByDesc('articles_models.id')->limit(10)->get();

            $banners = DB::table('banner_models')
            ->select('libelle', 'banner_image', 'img_url', 'id') // limiter les champs si besoin
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('plateform', 'all')
                    ->orWhere('plateform', 'quoideneuf');
            })
            ->whereIn('libelle', ['728X90','1200X1500','1920X309'])
            ->get()
            ->groupBy('libelle'); // regrouper par libellé

            // Extraire chaque bannière
            $banner_728X90_data = $banners->get('728X90')?->first();
            $banner_1920X309_data = $banners->get('1920X309')?->first();
            $banner_1200X1500_data = $banners->get('1200X1500')?->first();

            return [
                'une_news_data' => $une_news_data,
                'archive_news_data' => $archive_news_data,
                'politique_news_data' => $politique_news_data,
                'economie_news_data' => $economie_news_data,
                'popular_news_data' => $popular_news_data,
                'video_data' => $video_data,
                'event_keywords_data' => $event_keywords_data,
                'banner_1200X1500_data' => $banner_1200X1500_data,
                'banner_728X90_data' => $banner_728X90_data,
                'banner_1920X309_data' => $banner_1920X309_data,
                'news_rubrique' => $news_rubrique,
                'other_news_rubrique' => $other_news_rubrique,
                'weekend_news_data' => $weekend_news_data,
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

    public function get_frontend_news_details($slug)
    {
        try {
            if (!isset($slug)) :
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else :

                (int) $old_counter = DB::table('articles_models')->where('slug', $slug)
                    ->value('counter');

                $new_counter =  $old_counter+1;

                //return $new_counter;

                $is_updated = DB::table('articles_models')->where('slug', $slug)
                    ->update(['counter' => $new_counter]);

                if($is_updated){

                    $news_details_data =  DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                    ->select(
                        'rubriques_quoideneuf_models.rubrique',
                        'countries_models.pays',
                        'countries_models.flag',
                        'genre_journalistique_models.genre',
                        'articles_models.*'
                    )
                    ->where('articles_models.slug', $slug)->first();

                    $arry = [$news_details_data->rubrique_id];
                    $similar_news_data = DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select('rubriques_quoideneuf_models.rubrique','countries_models.pays','countries_models.flag','genre_journalistique_models.genre','articles_models.*')
                    ->where('articles_models.status',1)
                    ->whereIn('articles_models.rubrique_id', $arry)
                    ->where('articles_models.slug', '!=', $slug)
                    ->orderBy('id', 'desc')
                    ->limit(6)
                    ->get();


                    $popular_news_data = DB::table('articles_models')->orderByDesc('counter')
                    ->whereIn('articles_models.rubrique_id', $arry)
                    ->where('status', 1)->limit(9)->get();


                    $news_rubrique_data = RubriquesQuoideneufModels::orderBy('rubrique','asc')
                    ->get();

                    return [
                        'news_details_data' => $news_details_data,
                        'similar_news_data' => $similar_news_data,
                        'news_rubrique_data' => $news_rubrique_data,
                        'popular_news_data' => $popular_news_data,
                    ];
                }

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


    // get all news archives data
    public function get_frontend_all_archive_data()
    {
        try {
            return DB::table('articles_models')
            ->select(DB::raw('count(id) as `data`'), DB::raw("created_at as new_date"),  DB::raw('YEAR(created_at) year, MONTH(created_at) month'))
            ->groupby('year','month')->orderByDesc('created_at')->get();
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


    public function get_frontend_all_current_date_news_data()
    {
        try {
            $current_news_data =  DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select('rubriques_quoideneuf_models.rubrique','countries_models.pays','countries_models.flag','genre_journalistique_models.genre','articles_models.*')
            ->where('articles_models.status',1)
            ->whereDate('articles_models.created_at', '=', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->get();

            $popular_news_data = DB::table('articles_models')->orderByDesc('counter')->where('status', 1)->limit(9)->get();

            $banner_728X90_data = DB::table('banner_models')->where('libelle',"728X90")
            ->where('status',1)
            ->first();

            $news_rubrique = RubriquesQuoideneufModels::orderBy('rubrique','asc')->get();



            return [
                'current_news_data' => $current_news_data,
                'popular_news_data' => $popular_news_data,
                'banner_728X90_data' => $banner_728X90_data,
                'news_rubrique' => $news_rubrique,
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

    public function get_frontend_archive_article()
    {
        try {
            return DB::table('articles_models')
                ->select(DB::raw('count(id) as `data`'), DB::raw("created_at as new_date"),  DB::raw('YEAR(created_at) year, MONTH(created_at) month'))
                ->groupby('year','month')
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




    public function get_frontend_media_news(){
        try {
            return DB::table('media_models')->orderBy('id', 'desc')->limit(3)->get();
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


    public static function get_frontend_article_with_rubrique($slug)
    {

        try {
            $article = DB::table('articles_models')
                ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                ->select(
                    'rubriques_quoideneuf_models.rubrique',
                    'countries_models.pays',
                    'countries_models.flag',
                    'genre_journalistique_models.genre',
                    'articles_models.*'
                )
                ->where('rubriques_quoideneuf_models.slug',$slug)
                ->where('articles_models.status', 1)
                ->orderBy('articles_models.id','desc')
                ->limit(50)
                ->get();

                $arry = RubriquesQuoideneufModels::where('slug',$slug)->pluck('id');


                $popular_news_data = DB::table('articles_models')->orderByDesc('counter')
                ->whereIn('articles_models.rubrique_id', $arry)
                ->where('status', 1)->limit(9)->get();


                $news_rubrique_data = RubriquesQuoideneufModels::orderBy('rubrique','asc')
                ->get();

                $categorie = RubriquesQuoideneufModels::where('slug',$slug)
                ->value('rubrique');


            return [
                'article' => $article,
                'news_rubrique_data' => $news_rubrique_data,
                'popular_news_data' => $popular_news_data,
                'categorie' => $categorie
            ];
        } catch (\Throwable $e) {
            return $e->getMessage();
        }

    }

    public function get_frontend_archive_data($mounth,$year)
    {

        try {
            $ariches_news_data =  DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select(
                'rubriques_quoideneuf_models.rubrique',
                'countries_models.pays',
                'countries_models.flag',
                'genre_journalistique_models.genre',
                'articles_models.*'
            )
            ->whereMonth('articles_models.created_at', $mounth)
            ->whereYear('articles_models.created_at', $year)
            ->orderByDesc('articles_models.created_at')
            ->get();



            $popular_news_data = DB::table('articles_models')->orderByDesc('counter')
            ->where('status', 1)->limit(9)->get();


            $news_rubrique_data = RubriquesQuoideneufModels::orderBy('rubrique','asc')->get();

            return [
                'ariches_news_data' => $ariches_news_data,
                'popular_news_data' => $popular_news_data,
                'news_rubrique_data' => $news_rubrique_data
            ];

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

    public function get_frontend_event_keywords()
    {
        try {
            return  DB::table('events_key_words_models')->where('status', 0)->get();
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


    public function get_frontend_news_by_event_keywords($keyword)
    {
        try {
            return  DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
            ->select(
                'rubriques_quoideneuf_models.rubrique',
                'countries_models.pays',
                'countries_models.flag',
                'genre_journalistique_models.genre',
                'articles_models.*'
            )
            ->where('articles_models.titre','LIKE', '%'.$keyword.'%')
            ->orWhere('articles_models.lead','LIKE', '%'.$keyword.'%')
            ->orWhere('articles_models.contenus','LIKE', '%'.$keyword.'%')
            ->orderBy('id', 'desc')
            ->limit(150)
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
    public function filter_on_news_with_query($query)
    {
        try {
            return  DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
            ->select(
                'rubriques_quoideneuf_models.rubrique',
                'countries_models.pays',
                'countries_models.flag',
                'genre_journalistique_models.genre',
                'articles_models.*'
            )
            ->where('articles_models.titre','LIKE', '%'.$query.'%')
            ->orWhere('articles_models.lead','LIKE', '%'.$query.'%')
            ->orWhere('articles_models.contenus','LIKE', '%'.$query.'%')
            ->orderBy('id', 'desc')
            ->limit(150)
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




    public function get_frontend_news_rubriques()
    {
        $news_rubrique = RubriquesQuoideneufModels::whereIn('rubrique', ['INTERNATIONAL','SPORT','TRIBUNE LIBRE','SOCIETE','CULTURE'])
        ->orderBy('rubrique','asc')
        ->get();

        $other_news_rubrique = RubriquesQuoideneufModels::whereNotIn('rubrique', ['INTERNATIONAL','SPORT','TRIBUNE LIBRE','SOCIETE','CULTURE'])
        ->orderBy('rubrique','asc')
        ->get();


        return [
            'news_rubrique' => $news_rubrique,
            'other_news_rubriques' => $other_news_rubrique
        ];
    }

    public function get_frontend_other_rubrique()
    {
        $rubrique = RubriquesQuoideneufModels::whereNotIn('rubrique', ['POLITIQUE','ECONOMIE','SOCIETE'])
        ->orderBy('rubrique','asc')
        ->get();

        return $rubrique;
    }



    public function get_frontend_728X90()
    {
        try {
            return DB::table('banner_models')->where('libelle',"728X90")
                ->where('status',1)
                ->first();
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


    public function get_frontend_1920X309()
    {
        try {
            return DB::table('banner_models')->where('libelle',"1920X309")
                ->where('status',1)
                ->first();
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

    public function get_frontend_1200X1500()
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
            return $banner_1200X1500;
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

    public function get_frontend_media()
    {
        try {
            return DB::table('media_models')->orderBy('id', 'desc')->get();
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
