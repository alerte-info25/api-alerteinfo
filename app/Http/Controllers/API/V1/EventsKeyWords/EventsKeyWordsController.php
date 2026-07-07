<?php

namespace App\Http\Controllers\API\V1\EventsKeyWords;

use App\Http\Controllers\Controller;
use App\Models\EventsKeyWords\EventsKeyWordsModels;
use App\Services\CodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventsKeyWordsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function get()
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

    
    
    public function get_news_by_event_keywords($keyword)
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        try {
            if(empty($request->keywords)):
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "Le mot clé est obligatire 🤐"
                    ]
                );
            endif;
    
            $add = new EventsKeyWordsModels();
    
            $add->keywords = $request->keywords;
            $add->slug = CodeGenerator::generateRfk();
    
            if($add->save()) :
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Ok!, Le mot-clé a été enregistré avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Oupps!, L'enregistrement du mot-clé a échoué."
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, $slug)
    {

        try {
            if(empty($request->keywords)):
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "Le mot-clé est obligatire 🤐"
                    ]
                );
            endif;
    
    
            $update =  EventsKeyWordsModels::where('slug', $slug)->first();
    
            $update->keywords = $request->keywords;
    
            if($update->save()):
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Ok!,Le mot-clé a été modifié avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Oupps!, La modification du mot-clé a échoué."
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
    public function destroy($slug)
    {

        try {
            if (!$slug) :
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Oupps!, No element found"
                    ]
                );
            else :
                EventsKeyWordsModels::where('slug', $slug)->delete();
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => "Ok!, Deletion done"
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



    public function active_event_key_words($slug) {
        try {
            if(isset($slug)):
                $events_key_words_models = DB::table('events_key_words_models')->where('slug',$slug)->value('status');

                if((int)$events_key_words_models == 0):
                    $events_key_words_models_is_lock = DB::table('events_key_words_models')->where('slug',$slug)->update(['status' => 1]);

                    if(!$events_key_words_models_is_lock):
                        return response()->json(
                            [
                                'status' => 'error',
                                'code' => 300,
                                'message' => "Oupps!, Échec de l'activation du mot-clé, veuillez réessayer!"
                            ]
                        );
                    else:
                        return response()->json(
                            [
                                'status' => 'success',
                                'code' => 200,
                                'message' => "Ok!, le mot-clé été activé avec succès."
                            ]
                        );
                    endif;
                else:
                    $events_key_words_unLock = DB::table('events_key_words_models')->where('slug',$slug)->update(['status' => 0]);

                    if(!$events_key_words_unLock):
                        return response()->json(
                            [
                                'status' => 'error',
                                'code' => 300,
                                'message' => "Oupps!, Échec de la désactivation du mot-clé, veuillez réessayer!"
                            ]
                        );
                    else:
                        return response()->json(
                            [
                                'status' => 'success',
                                'code' => 200,
                                'message' => "Ok!,Le mot-clé a été désactivé avec succès"
                            ]
                        );
                    endif;
                endif;
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Oupps!, Aucune donnée trouvée"
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
