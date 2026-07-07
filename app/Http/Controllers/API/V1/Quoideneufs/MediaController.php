<?php

namespace App\Http\Controllers\API\V1\Quoideneufs;

use App\Http\Controllers\Controller;
use App\Models\Quoideneufs\MediaModels;
use App\Services\CodeGenerator;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{

    public $audio;

    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return MediaModels::all();
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

    public function get_recente_media(){

        try {
            return DB::table('media_models')->orderBy('id', 'desc')->limit(2)->get();
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            if ($request->type_media == "vidéo") :

                if (empty($request->video_url)) :
                    return response()->json(
                        [
                            'status' => 'erreur',
                            'code' => "302",
                            'message' => "La clé de la vidéo est obligatoire"
                        ]
                    );
                endif;


                $add_media = new MediaModels();

                $add_media->video_url = $request->video_url;
                $add_media->description = $request->description;
                $add_media->author = $request->author;
                $add_media->type_media = $request->type_media;
                $add_media->slug = CodeGenerator::generateRfk();

                if($add_media->save()) :
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => 'Ok!, Le média a été enregistré avec succès'
                        ]
                    );
                else:
                    return response()->json(
                        [
                            'status' => 'erreur',
                            'code' => 300,
                            'message' => "Erreur ! Échec de l'enregistrement du média, veuillez réessayer!"
                        ]
                    );
                endif;
            endif;


            if ($request->type_media == "audio") :

                if (empty($request->audio)) :
                    return response()->json(
                        [
                            'status' => 'erreur',
                            'code' => "302",
                            'message' => "Le fichier de l'audio est obligatoire"
                        ]
                    );
                endif;

                $this->audio = UploadService::upload_audio($request);

                if($this->audio == "error"):
                    return response()->json(
                        [
                            'code' => "302",
                            'message' => "L'enregistrement de l'audio a échoué."
                        ]
                    );
                endif;

                $add_media = new MediaModels();

                if($this->audio == "file_not_found"):
                    $add_media->audio_url = $this->audio;
                endif;
                $add_media->description = $request->description;
                $add_media->author = $request->author;
                $add_media->type_media = $request->type_media;
                $add_media->slug = CodeGenerator::generateRfk();

                if($add_media->save()) :
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => "Ok!, L'audio a été enregistrer avec succès"
                        ]
                    );
                else:
                    return response()->json(
                        [
                            'status' => 'erreur',
                            'code' => 300,
                            'message' => "Erreur ! Échec de l'enregistrement de l'audio, veuillez réessayer!"
                        ]
                    );
                endif;

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


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        try {

            if ($request->type_media == "vidéo") :

                if (empty($request->video_url)) :
                    return response()->json(
                        [
                            'status' => 'erreur',
                            'code' => "302",
                            'message' => "La clé de la vidéo est obligatoire"
                        ]
                    );
                endif;



                $add_media =  MediaModels::where('slug',$slug)->first();

                $add_media->video_url = $request->video_url;
                $add_media->description = $request->description;
                $add_media->author = $request->author;
                $add_media->type_media = $request->type_media;

                if($add_media->save()) :
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => 'Ok!, Le média a été modifié avec succès'
                        ]
                    );
                else:
                    return response()->json(
                        [
                            'status' => 'erreur',
                            'code' => 300,
                            'message' => "Erreur ! Échec de la modification du média, veuillez réessayer!"
                        ]
                    );
                endif;
            endif;


            if ($request->type_media == "audio") :

                if (empty($request->audio)) :
                    return response()->json(
                        [
                            'status' => 'erreur',
                            'code' => "302",
                            'message' => "Le fichier de l'audio est obligatoire"
                        ]
                    );
                endif;

                $this->audio = UploadService::upload_audio($request);

                if($this->audio == "error"):
                    return response()->json(
                        [
                            'code' => "302",
                            'message' => "L'enregistrement de l'audio a échoué."
                        ]
                    );
                endif;

                $add_media =  MediaModels::where('slug',$slug)->first();

                if($this->audio == "file_not_found"):
                    $add_media->audio_url = $this->audio;
                endif;
                $add_media->description = $request->description;
                $add_media->author = $request->author;
                $add_media->type_media = $request->type_media;

                if($add_media->save()) :
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => "Ok!, L'audio a été modifié avec succès"
                        ]
                    );
                else:
                    return response()->json(
                        [
                            'status' => 'erreur',
                            'code' => 300,
                            'message' => "Erreur ! Échec de la modification de l'audio, veuillez réessayer!"
                        ]
                    );
                endif;

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
                        'message' => "Erreur!, Aucun element trouvé"
                    ]
                );
            else :

                DB::table('media_models')->where('slug', $slug)->delete();
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok!, Suppression éffectuée"
                    ]
                );

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
}
