<?php

namespace App\Http\Controllers\API\V1\Statistiques;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminStatistiquesController extends Controller
{

    public function default_statistiques()
    {
        try {
            $all_users = DB::table('administration_models')->count();
            $all_abonnes = DB::table('abonnes_mobile_models')->count();
            $all_abonnements = DB::table('abonnements_mobile_models')->count();

            return [
                'all_users' => $all_users,
                'all_abonnes' => $all_abonnes,
                'all_abonnements' => $all_abonnements,
            ];



        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function get_all_journaliste ()
    {
        try {

            return DB::table('administration_models')
            ->join('users', 'administration_models.user_id', '=', 'users.id')
            ->whereIn('users.user_type', ['redaction','quoideneuf'])

            ->where('administration_models.status', 1)
            ->select('administration_models.first_name', 'administration_models.last_name')
            ->get();

        } catch (\Throwable $th) {
            return response()->json([
                'code' => 302,
                'status' => 'Erreur',
                'message' => $th->getMessage(),
            ]);
        }
    }


    /**
     * Summary of get_quoideneuf_productions
     * @return array|mixed|\Illuminate\Http\JsonResponse
     */
    public function get_quoideneuf_productions ()
    {
        try {
            $productions = DB::table('articles_models')
            //->join('journalists', 'articles.journalist_id', '=', 'journalists.id')
            ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select(
                'genre_journalistique_models.genre',
                DB::raw('MONTH(articles_models.created_at) as month'),
                DB::raw('YEAR(articles_models.created_at) as year'),
                DB::raw('COUNT(articles_models.id) as total_productions')
            )
            ->groupBy('articles_models.genre_id', DB::raw('YEAR(articles_models.created_at)'), DB::raw('MONTH(articles_models.created_at)'))
            ->orderBy('year', 'asc')
            ->orderBy('month', 'desc')
            ->whereYear('articles_models.created_at', date('Y', strtotime(now())))

            ->get();


            $data_transformed = $this->dataTransformers($productions);

            $month_data  =  ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Aoû", "Sep", "Oct", "Nov", "Déc"];
            return [
                'data' => $data_transformed,
                'months' => $month_data,
            ];

        } catch (\Throwable $th) {
            return response()->json([
                'code' => 302,
                'status' => 'Erreur',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function get_filter_on_quoideneuf_productions (Request $request)
    {
        try {

            if($request->journaliste == "All") {

                $productions = DB::table('articles_models')
                //->join('journalists', 'articles.journalist_id', '=', 'journalists.id')
                ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'genre_journalistique_models.genre',
                    DB::raw('MONTH(articles_models.created_at) as month'),
                    DB::raw('YEAR(articles_models.created_at) as year'),
                    DB::raw('COUNT(articles_models.id) as total_productions')
                )
                ->groupBy('articles_models.genre_id', DB::raw('YEAR(articles_models.created_at)'), DB::raw('MONTH(articles_models.created_at)'))
                ->orderBy('year', 'asc')
                ->orderBy('month', 'desc')
                ->whereYear('articles_models.created_at', $request->year)
    
                ->get();
    
    
                $data_transformed = $this->dataTransformers($productions);
    
                $month_data  =  ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Aoû", "Sep", "Oct", "Nov", "Déc"];
                return [
                    'data' => $data_transformed,
                    'months' => $month_data,
                ];
            }else {

                $productions = DB::table('articles_models')
                //->join('journalists', 'articles.journalist_id', '=', 'journalists.id')
                ->join('genre_journalistique_models', 'articles_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'genre_journalistique_models.genre',
                    DB::raw('MONTH(articles_models.created_at) as month'),
                    DB::raw('YEAR(articles_models.created_at) as year'),
                    DB::raw('COUNT(articles_models.id) as total_productions')
                )
                ->groupBy('articles_models.genre_id', DB::raw('YEAR(articles_models.created_at)'), DB::raw('MONTH(articles_models.created_at)'))
                ->orderBy('year', 'asc')
                ->orderBy('month', 'desc')
                ->whereYear('articles_models.created_at', $request->year)
                ->where('articles_models.author', 'LIKE', '%'. $request->journaliste. '%')
                ->get();
    
    
                $data_transformed = $this->dataTransformers($productions);
    
                $month_data  =  ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Aoû", "Sep", "Oct", "Nov", "Déc"];
                return [
                    'data' => $data_transformed,
                    'months' => $month_data,
                ];
            }

        } catch (\Throwable $th) {
            return response()->json([
                'code' => 302,
                'status' => 'Erreur',
                'message' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Summary of get_quoideneuf_productions
     * @return array|mixed|\Illuminate\Http\JsonResponse
     * 
     * END Summary
     */

     
    /**
     * Summary of get_alerte_info_production
     * @return array|mixed|\Illuminate\Http\JsonResponse
     */

    public function get_alerte_info_production (){
        try {
            $productions = DB::table('depeche_models')
            //->join('journalists', 'articles.journalist_id', '=', 'journalists.id')
            ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
            ->select(
                'genre_journalistique_models.genre',
                DB::raw('MONTH(depeche_models.created_at) as month'),
                DB::raw('YEAR(depeche_models.created_at) as year'),
                DB::raw('COUNT(depeche_models.id) as total_productions')
            )
            ->groupBy('depeche_models.genre_id', DB::raw('YEAR(depeche_models.created_at)'), DB::raw('MONTH(depeche_models.created_at)'))
            ->orderBy('year', 'asc')
            ->orderBy('month', 'desc')
            ->whereYear('depeche_models.created_at', date('Y', strtotime(now())))

            ->get();


            $data_transformed = $this->dataTransformers($productions);

            $month_data  =  ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Aoû", "Sep", "Oct", "Nov", "Déc"];
            return [
                'data' => $data_transformed,
                'months' => $month_data,
            ];

        } catch (\Throwable $th) {
            return response()->json([
                'code' => 302,
                'status' => 'Erreur',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function get_filter_on_alerte_info_productions (Request $request)
    {
        try {

            if($request->journaliste == "All") {

                $productions = DB::table('depeche_models')
                //->join('journalists', 'articles.journalist_id', '=', 'journalists.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'genre_journalistique_models.genre',
                    DB::raw('MONTH(depeche_models.created_at) as month'),
                    DB::raw('YEAR(depeche_models.created_at) as year'),
                    DB::raw('COUNT(depeche_models.id) as total_productions')
                )
                ->groupBy('depeche_models.genre_id', DB::raw('YEAR(depeche_models.created_at)'), DB::raw('MONTH(depeche_models.created_at)'))
                ->orderBy('year', 'asc')
                ->orderBy('month', 'desc')
                ->whereYear('depeche_models.created_at', $request->year)
                ->get();
    
    
                $data_transformed = $this->dataTransformers($productions);
    
                $month_data  =  ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Aoû", "Sep", "Oct", "Nov", "Déc"];
                return [
                    'data' => $data_transformed,
                    'months' => $month_data,
                ];
            }else {

                $productions = DB::table('depeche_models')
                //->join('journalists', 'articles.journalist_id', '=', 'journalists.id')
                ->join('genre_journalistique_models', 'depeche_models.genre_id', '=', 'genre_journalistique_models.id')
                ->select(
                    'genre_journalistique_models.genre',
                    DB::raw('MONTH(depeche_models.created_at) as month'),
                    DB::raw('YEAR(depeche_models.created_at) as year'),
                    DB::raw('COUNT(depeche_models.id) as total_productions')
                )
                ->groupBy('depeche_models.genre_id', DB::raw('YEAR(depeche_models.created_at)'), DB::raw('MONTH(depeche_models.created_at)'))
                ->orderBy('year', 'asc')
                ->orderBy('month', 'desc')
                ->whereYear('depeche_models.created_at', $request->year)
                ->where('depeche_models.author', 'LIKE', '%'. $request->journaliste. '%')
                ->get();
    
    
                $data_transformed = $this->dataTransformers($productions);
    
                $month_data  =  ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Aoû", "Sep", "Oct", "Nov", "Déc"];
                return [
                    'data' => $data_transformed,
                    'months' => $month_data,
                ];
            }

        } catch (\Throwable $th) {
            return response()->json([
                'code' => 302,
                'status' => 'Erreur',
                'message' => $th->getMessage(),
            ]);
        }
    }

    private function dataTransformers($productions){
        $groupedData = [];

        foreach ($productions as $production) {
            $name = $production->genre . ' - ' . $production->year;
            
            // Si le genre avec l'année n'est pas encore dans le tableau, on l'ajoute
            if (!isset($groupedData[$name])) {
                $groupedData[$name] = [
                    'name' => $name,
                    'data' => array_fill(0, 12, 0) // Initialise avec 12 mois à 0
                ];
            }
            
            // Ajouter les productions pour le mois correspondant
            $groupedData[$name]['data'][$production->month - 1] = $production->total_productions;
        }

        // Finaliser le tableau dans le format attendu
        $result = array_values($groupedData);

        // Renvoie la réponse en JSON
        return response()->json($result);
    }
}
