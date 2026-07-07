<?php

namespace App\Http\Controllers\API\V1\Quoideneufs;

use DateTime;
use DateInterval;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\CodeGenerator;
use App\Models\Views\ViewsModels;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Quoideneufs\ArticlesModels;

class ArticlesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $get_article = DB::table('articles_models')
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
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get();

            $count_article_quoi_de_neufs = DB::table('articles_models')->count();

            return response()->json(
                [
                    'article' => $get_article,
                    'article_number' => $count_article_quoi_de_neufs,
                ]
            );
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


    // GET FRONTEND HOME ARTICLE
    public function get_frontend_home_article()
    {
        try {
            return DB::table('articles_models')
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
                ->where('articles_models.status', 1)
                ->orderBy('id', 'desc')
                ->limit(6)
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



    public function get_frontend_une_news_article()
    {
        try {
            $home_news = DB::table('articles_models')
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
                ->where('articles_models.status', 1)
                ->orderBy('id', 'desc')
                ->limit(6)
                ->get();


            $popular_article = DB::table('articles_models')->orderByDesc('counter')->where('status', 1)->limit(8)
                ->get();

            $olders_news = DB::table('articles_models')
                ->select(DB::raw('count(id) as `data`'), DB::raw("created_at as new_date"), DB::raw('YEAR(created_at) year, MONTH(created_at) month'))
                ->groupby('year', 'month')
                ->limit(12)
                ->orderByDesc('created_at')
                ->get();


            return [
                'home_news' => $home_news,
                'popular_article' => $popular_article,
                'olders_news' => $olders_news
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

    //countries_models

    public static function get_article_with_rubrique($slug)
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
                ->where('rubriques_quoideneuf_models.slug', $slug)
                ->where('articles_models.status', 1)
                ->orderBy('articles_models.id', 'desc')
                ->limit(50)
                ->get();

            $rubrique = DB::table('rubriques_quoideneuf_models')->where('slug', $slug)->value('rubrique');

            return [
                'article' => $article,
                'categorie' => $rubrique
            ];
        } catch (\Throwable $e) {
            return $e->getMessage();
        }

    }

    public static function get_article_by_rubrique($rubrique)
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
                ->where('rubriques_quoideneuf_models.rubrique', $rubrique)
                ->where('articles_models.status', 1)
                ->orderBy('articles_models.id', 'desc')
                ->limit(6)
                ->get();

            return $article;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }

    }



    public function get_on_backend()
    {

        try {
            return DB::table('articles_models')
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
                ->orderByDesc('articles_models.id')
                ->limit(100)
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
            if (empty($request->titre)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le titre de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->rubrique_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "La rubrique de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->genre_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le genre journalistique de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->pays_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le pays de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->lead)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le lead de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->contenus)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le contenu de l'article est obligatoire"
                    ]
                );
            endif;


            if (empty($request->media_url)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Vous n'avez pas choisir d'image"
                    ]
                );
            endif;

            $news_already_published = ArticlesModels::where('titre', $request->titre)->first();
            if ($news_already_published != null):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "L'article existe déjà, il ne peut pas être enregistré une seconde fois. Prière de modifier si nécessaire."
                    ]
                );
            endif;

            $add_article = new ArticlesModels();

            $add_article->titre = $request->titre;
            $add_article->rubrique_id = $request->rubrique_id;
            $add_article->genre_id = $request->genre_id;
            $add_article->pays_id = $request->pays_id;
            $add_article->author = $request->author;
            $add_article->lead = $request->lead;
            $add_article->legende = $request->legende;
            $add_article->contenus = $request->contenus;
            $add_article->media_url = $request->media_url;
            $add_article->slug = Str::slug(now()->format('YmdHis').'-'.$request->titre);

            if ($add_article->save()):

                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'slug' => $add_article->slug,
                        'message' => "Ok ! L'article a été enregistré avec succès."
                    ]
                );
            else:

                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement de l'article, veuillez réessayer!"
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
    public function detail_article_on_backend($slug)
    {

        //return $slug;
        try {
            if (!$slug):
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else:
                return DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    //->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                    ->select(
                        'rubriques_quoideneuf_models.rubrique',
                        //'countries_models.pays',
                        //'countries_models.flag',
                        'genre_journalistique_models.genre',
                        'articles_models.*'
                    )
                    ->where('articles_models.slug', $slug)->first();

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
            if (empty($request->titre)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le titre de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->rubrique_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "La rubrique de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->genre_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le genre journalistique de l'article est obligatoire"
                    ]
                );
            endif;

            if (empty($request->pays_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le pays de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->lead)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le lead de l'article est obligatoire"
                    ]
                );
            endif;
            if (empty($request->contenus)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Le contenu de l'article est obligatoire"
                    ]
                );
            endif;


            if (empty($request->media_url)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Vous n'avez pas choisir d'image"
                    ]
                );
            endif;

            $update_article = ArticlesModels::where('slug', $slug)->first();

            $update_article->titre = $request->titre;
            $update_article->rubrique_id = $request->rubrique_id;
            $update_article->genre_id = $request->genre_id;

            $update_article->pays_id = $request->pays_id;
            $update_article->lead = $request->lead;
            $update_article->legende = $request->legende;
            $update_article->contenus = $request->contenus;
            $update_article->media_url = $request->media_url;
            $update_article->slug = Str::slug($request->titre);

            if ($update_article->save()):

                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'slug' => $update_article->slug,
                        'message' => "Ok ! L'article a été modifié avec succès."
                    ]
                );
            else:

                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification de l'article, veuillez réessayer!"
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
            if (!$slug):
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else:
                ArticlesModels::where('slug', $slug)->delete();

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
                    'code' => 300,
                    'message' => $e->getMessage(),
                ]
            );
        }
    }


    public function push_article($slug)
    {
        try {
            if (!$slug):
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else:
                $status = ArticlesModels::where('slug', $slug)->value('status');
                if ($status == 1):
                    ArticlesModels::where('slug', $slug)->update(['status' => 0]);
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => "Ok ! Votre article a été retiré en ligne."
                        ]
                    );
                else:
                    ArticlesModels::where('slug', $slug)->update(
                        [
                            'status' => 1,
                            'created_at' => now()->format('Y-m-d H:i:s'),
                            'updated_at' => now()->format('Y-m-d H:i:s'),
                        ]
                    );
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => "Ok ! Votre article a été publié en ligne."
                        ]
                    );
                endif;
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

    // OTHER REQUEST


    // POPULAR ARTICLE
    public function get_popular_article()
    {
        try {

            $popular_article = DB::table('articles_models')->where('counter', '>=', 50)
                ->where('status', 1)->limit(6)->get();

            if (count($popular_article) > 0):
                return $popular_article;
            else:
                return DB::table('articles_models')
                    ->orderByDesc('counter')
                    ->where('status', 1)
                    ->limit(6)
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



    // GET ARTICLE SIMILAIRE
    public function get_similar_article($rubrique, $slug)
    {

        try {
            $arry = [$slug];

            $similar_article = DB::table('articles_models')

                ->where('rubrique_id', $rubrique)
                ->whereNotIn('slug', $arry)
                ->where('status', 1)
                ->limit(6)
                ->get();

            return $similar_article;
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



    // GET CUSTOMER ARTICLES
    public function get_customer_news($customer)
    {

        try {
            if (!isset($customer)):
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun élément trouvé"
                    ]
                );
            else:
                $customer_news = DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select(
                        'rubriques_quoideneuf_models.rubrique',
                        'genre_journalistique_models.genre',
                        'countries_models.pays',
                        'countries_models.flag',
                        'articles_models.*'
                    )
                    ->where('articles_models.author', 'LIKE', '%' . $customer . '%')
                    ->orderByDesc('articles_models.id')
                    ->get();



                $count_article = DB::table('articles_models')->where('author', 'LIKE', '%' . $customer . '%')->count();

                $article_ispublished = DB::table('articles_models')->where('author', 'LIKE', '%' . $customer . '%')->where('status', 1)->count();
                $article_nopublished = DB::table('articles_models')->where('author', 'LIKE', '%' . $customer . '%')->where('status', 0)->count();

                return response()->json(
                    [
                        'customer_list_news' => $customer_news,
                        'customer_total_news' => $count_article,
                        'customer_news_ispublished' => $article_ispublished,
                        'customer_news_nopublished' => $article_nopublished,
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



    public function filter_on_customer_news(Request $request)
    {

        //return $request->all();
        try {
            $date_debut = date('Y-m-d', strtotime($request->date_debut));
            $date_fin = new DateTime(date('Y-m-d', strtotime($request->date_fin)));


            $date_fin = $date_fin->add(new DateInterval('P1D'));
            $date_fin = $date_fin->format('Y-m-d');

            $myArry = array($date_debut, $date_fin);

            

            if (count($request->rubrique_id) == 0 && count($request->genre_id) == 0):

                //return $request->all();
                return DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select(
                        'rubriques_quoideneuf_models.rubrique',
                        'genre_journalistique_models.genre',
                        'countries_models.pays',
                        'countries_models.flag',
                        'articles_models.*'
                    )
                    ->whereBetween('articles_models.created_at', $myArry)
                    ->where('articles_models.author', 'LIKE', '%' . $request->customer . '%')
                    ->orderBy('articles_models.id', 'asc')
                    ->get();
            endif;

            if (count($request->rubrique_id) > 0 && count($request->genre_id) == 0):
                return DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select(
                        'rubriques_quoideneuf_models.rubrique',
                        'genre_journalistique_models.genre',
                        'countries_models.pays',
                        'countries_models.flag',
                        'articles_models.*'
                    )
                    ->whereIn('rubrique_id', $request->rubrique_id)
                    ->where('articles_models.author', 'LIKE', '%' . $request->customer . '%')
                    ->whereBetween('articles_models.created_at', $myArry)
                    ->orderBy('id', 'desc')
                    ->get();
            endif;


            if (count($request->rubrique_id) == 0 && count($request->genre_id) > 0):
                return DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select(
                        'rubriques_quoideneuf_models.rubrique',
                        'genre_journalistique_models.genre',
                        'countries_models.pays',
                        'countries_models.flag',
                        'articles_models.*'
                    )
                    ->whereIn('genre_id', $request->genre_id)
                    ->where('articles_models.author', 'LIKE', '%' . $request->customer . '%')
                    ->whereBetween('articles_models.created_at', [$date_debut, $date_fin])
                    ->orderBy('id', 'desc')
                    ->get();
            endif;


            if (count($request->rubrique_id) > 0 && count($request->genre_id) > 0):
                return DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select(
                        'rubriques_quoideneuf_models.rubrique',
                        'genre_journalistique_models.genre',
                        'countries_models.pays',
                        'countries_models.flag',
                        'articles_models.*'
                    )
                    ->whereIn('genre_id', $request->genre_id)
                    ->whereIn('rubrique_id', $request->rubrique_id)
                    ->whereBetween('articles_models.created_at', [$date_debut, $date_fin])
                    ->where('articles_models.author', 'LIKE', '%' . $request->customer . '%')
                    ->orderBy('id', 'desc')
                    ->get();
            endif;
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


    // DETAIL ARTICLE SUR QUOIDENEUF

    /**
     * 
     * 
     * 
     public function detail_article_quoideneuf(Request $request)
     {
 
         //return $request->all();
         $visitor_ip = $request->ip();
         $news_slug = $request->slug;
 
         try {
             if (!isset($request->slug)) :
                 return response()->json(
                     [
                         'status' => 'error',
                         'code' => 404,
                         'message' => "Erreur!, Aucun élément trouvé"
                     ]
                 );
             else :
 
                 $is_view = DB::table('views_models')->where('news_slug', $news_slug)
                     ->where('ip_address', $visitor_ip)
                     ->first();
 
                 if ($is_view == null) :
                     $new_view = new ViewsModels();
 
                     $new_view->news_slug = $news_slug;
                     $new_view->ip_address = $visitor_ip;
 
                     if ($new_view->save()) :
 
                         $views_counter = DB::table('views_models')
                             ->select(DB::raw('count(*) as news_counter, news_slug'))
                             ->where('news_slug', $news_slug)
                             ->groupBy('news_slug')
                             ->first();
                         //return $views_counter;
                         DB::table('articles_models')->where('slug', $news_slug)
                             ->update(['counter' => $views_counter->news_counter]);
                     endif;
 
 
                 endif;
 
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
                     ->where('articles_models.slug', $news_slug)->first();
 
                 $is_liked = DB::table('like_models')->where('news_slug', $news_slug)
                     ->where('ip_address', $visitor_ip)
                     ->first();
 
 
                 $is_disliked = DB::table('dislike_models')->where('news_slug', $news_slug)
                     ->where('ip_address', $visitor_ip)
                     ->first();
 
                 if ($is_liked != null) :
                     return [
                         'article_detail' => $article,
                         'visitor_status' => "is_view"
                     ];
                 elseif($is_disliked != null) :
                     return [
                         'article_detail' => $article,
                         'visitor_status' => "is_view"
                     ];
                 else:
                     return [
                         'article_detail' => $article,
                         'visitor_status' => "not_view"
                     ];
                 endif;
 
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
     */


    // GESTION DES ARCHIVE SUR QUOIDENEUF

    public function get_articles_archive()
    {

        try {
            return DB::table('articles_models')
                ->select(DB::raw('count(id) as `data`'), DB::raw("created_at as new_date"), DB::raw('YEAR(created_at) year, MONTH(created_at) month'))
                ->groupby('year', 'month')
                ->limit(12)
                ->orderByDesc('created_at')
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
    public function get_archive_data($mounth, $year)
    {

        try {
            return DB::table('articles_models')
                ->whereMonth('created_at', $mounth)
                ->whereYear('created_at', $year)
                ->orderByDesc('created_at')
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


    // FILTER ARTICLE

    public function filter_on_news(Request $request)
    {

        //return $request->all();
        try {
            $date_debut = date('Y-m-d', strtotime($request->date_debut));
            $date_fin = new DateTime(date('Y-m-d', strtotime($request->date_fin)));

            $date_fin = $date_fin->add(new DateInterval('P1D'));
            $date_fin = $date_fin->format('Y-m-d');

            $myArry = array($date_debut, $date_fin);


            if (count($request->rubrique_id) == 0 && count($request->genre_id) == 0):

                //return $request->all();
                return DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select('rubriques_quoideneuf_models.rubrique', 'genre_journalistique_models.genre', 'articles_models.*')
                    //->whereIn('rubrique_id', $request->rubrique_id)
                    ->whereBetween('articles_models.created_at', $myArry)
                    ->orderBy('articles_models.id', 'asc')
                    ->get();
            endif;

            if (count($request->rubrique_id) > 0 && count($request->genre_id) == 0):
                return DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select('rubriques_quoideneuf_models.rubrique', 'genre_journalistique_models.genre', 'articles_models.*')
                    ->whereIn('rubrique_id', $request->rubrique_id)
                    ->whereBetween('articles_models.created_at', $myArry)
                    ->orderBy('id', 'desc')
                    ->get();
            endif;


            if (count($request->rubrique_id) == 0 && count($request->genre_id) > 0):
                return DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select('rubriques_quoideneuf_models.rubrique', 'genre_journalistique_models.genre', 'articles_models.*')
                    ->whereIn('genre_id', $request->genre_id)
                    ->whereBetween('articles_models.created_at', [$date_debut, $date_fin])
                    ->orderBy('id', 'desc')
                    ->get();
            endif;


            if (count($request->rubrique_id) > 0 && count($request->genre_id) > 0):
                return DB::table('articles_models')
                    ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
                    ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                    ->select('rubriques_quoideneuf_models.rubrique', 'genre_journalistique_models.genre', 'articles_models.*')
                    ->whereIn('genre_id', $request->genre_id)
                    ->whereIn('rubrique_id', $request->rubrique_id)
                    ->whereBetween('articles_models.created_at', [$date_debut, $date_fin])
                    ->orderBy('id', 'desc')
                    ->get();
            endif;
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

    // GET FLASH INFO ARTICLE

    public function get_flash_info()
    {
        try {
            $get_article = DB::table('articles_models')
                ->orderBy('id', 'desc')->where('status', 1)->limit(8)->get();

            return $get_article;
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

    // RECENTE ARTICLES

    public function get_recente_article()
    {

        try {
            $get_article = DB::table('articles_models')
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
                ->orderBy('id', 'desc')
                ->limit(10)->get();

            $count_article = DB::table('articles_models')->count();

            $article_ispublished = DB::table('articles_models')->where('status', 1)->count();
            $article_nopublished = DB::table('articles_models')->where('status', 0)->count();

            return response()->json(
                [
                    'article_quoi_de_neufs' => $get_article,
                    'article_number' => $count_article,
                    'article_ispublished' => $article_ispublished,
                    'article_nopublished' => $article_nopublished,
                ]
            );
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

    // STATISTIQUE

    public function get_article_hebdo_statistique(Request $request)
    {

        $date_debut = $request->filled('startDate') 
        ? Carbon::parse($request->startDate)->startOfDay() 
        : now()->subDays(6)->startOfDay();
        $date_fin = $request->filled('endDate') 
        ? Carbon::parse($request->endDate)->endOfDay() 
        : now()->endOfDay();

        try {
            $totalArticles = $this->getTotalArticles($date_debut, $date_fin);
            $byCountry = $this->getArticlesByCountry($date_debut, $date_fin);
            $byAuthor = $this->getArticlesByAuthorWithDetails($date_debut, $date_fin);
            $byRubrique = $this->getArticlesByRubriqueWithDetails($date_debut, $date_fin);
            $byGenre = $this->getArticlesByGenreWithDetails($date_debut, $date_fin);


            return response()->json([
                'total_articles' => $totalArticles,
                'articles_by_country' => $byCountry,
                'articles_by_author' => $byAuthor,
                'articles_by_rubrique' => $byRubrique,
                'articles_by_genre' => $byGenre,
            ]);
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



    // ----------------------------
    // SOUS-FONCTIONS PRIVÉES
    // ----------------------------

    private function getTotalArticles($date_debut, $date_fin)
    {
        return DB::table('articles_models')
            ->whereBetween('created_at', [$date_debut, $date_fin])
            ->where('status', 1)
            ->count();
    }

    private function getArticlesByCountry($date_debut, $date_fin)
    {
        return DB::table('articles_models')
            ->join('countries_models', 'articles_models.pays_id', '=', 'countries_models.id')
            ->whereBetween('articles_models.created_at', [$date_debut, $date_fin])
            ->where('articles_models.status', 1)
            ->select('countries_models.iso_code', 'countries_models.pays', DB::raw('COUNT(*) as total'))
            ->groupBy('countries_models.iso_code', 'countries_models.pays')
            ->get()
            ->map(fn($item) => [
                'iso_code' => $item->iso_code,
                'pays' => $item->pays,
                'total' => (int) $item->total,
            ])
            ->values()
            ->all();
    }


    private function getArticlesByAuthorWithDetails($date_debut, $date_fin)
    {
        // Étape 1 : Récupérer tous les articles valides dans la plage
        $articles = DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->whereBetween('articles_models.created_at', [$date_debut, $date_fin])
            ->where('articles_models.status', 1)
            ->select(
                'rubriques_quoideneuf_models.rubrique as rubrique', 
                'genre_journalistique_models.genre as genre',
                'articles_models.author', 
                'articles_models.id', 
                'articles_models.titre',
                'articles_models.slug',
                'articles_models.status', 
                'articles_models.created_at'
            )
            ->get();
            

        // Étape 2 : Grouper par auteur
        return $articles->groupBy('author')->map(function ($group, $author) {
            return [
                'author' => $author,
                'total' => $group->count(),
                'articles' => $group->map(fn($item) => [
                    'id' => $item->id,
                    'titre' => $item->titre,
                    'slug' => $item->slug,
                    'status' => $item->status,
                    'rubrique' => $item->rubrique,
                    'genre' => $item->genre,
                    'created_at' => $item->created_at,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    private function getArticlesByRubriqueWithDetails($date_debut, $date_fin)
    {
        $articles = DB::table('articles_models')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->whereBetween('articles_models.created_at', [$date_debut, $date_fin])
            ->where('articles_models.status', 1)
            ->select(
                'rubriques_quoideneuf_models.rubrique as rubrique', 
                'genre_journalistique_models.genre as genre',
                'articles_models.id', 
                'articles_models.titre',
                'articles_models.slug',
                'articles_models.status',
                'articles_models.created_at'
            )
            ->get();

        return $articles->groupBy('rubrique')->map(function ($group, $rubrique) {
            return [
                'rubrique' => $rubrique,
                'total' => $group->count(),
                'articles' => $group->map(fn($item) => [
                    'id' => $item->id,
                    'titre' => $item->titre,
                    'slug' => $item->slug,
                    'status' => $item->status,
                    'rubrique' => $item->rubrique,
                    'genre' => $item->genre,
                    'created_at' => $item->created_at,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    private function getArticlesByGenreWithDetails($date_debut, $date_fin)
    {
        $articles = DB::table('articles_models')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->join('rubriques_quoideneuf_models', 'articles_models.rubrique_id', '=', 'rubriques_quoideneuf_models.id')
            ->whereBetween('articles_models.created_at', [$date_debut, $date_fin])
            ->where('articles_models.status', 1)
            ->select(
                'genre_journalistique_models.genre as genre',
                'rubriques_quoideneuf_models.rubrique as rubrique',
                'articles_models.id', 
                'articles_models.titre', 
                'articles_models.slug',
                'articles_models.status',
                'articles_models.created_at'
            )
            ->get();

        return $articles->groupBy('genre')->map(function ($group, $genre) {
            return [
                'genre' => $genre,
                'total' => $group->count(),
                'articles' => $group->map(fn($item) => [
                    'id' => $item->id,
                    'titre' => $item->titre,
                    'slug' => $item->slug,
                    'status' => $item->status,
                    'genre' => $item->genre,
                    'rubrique' => $item->rubrique,
                    'created_at' => $item->created_at,
                ])->values()->all(),
            ];
        })->values()->all();
    }



}
