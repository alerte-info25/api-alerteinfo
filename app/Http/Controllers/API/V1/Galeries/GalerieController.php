<?php

namespace App\Http\Controllers\API\V1\Galeries;

use App\Http\Controllers\Controller;
use App\Models\Galeries\GalerieModels;
use App\Models\Galeries\GaleriesModels;
use App\Services\CodeGenerator;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GalerieController extends Controller
{
    public $galerie_img_url;

    public function __construct()
    {
        //$this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return DB::table('galerie_models')->orderByDesc('created_at')->limit(100)->get();
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


    public function get_galerie_limited()
    {
        try {
            return DB::table('galerie_models')->orderByDesc('created_at')->limit(24)->get();
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

    public function check($query)
    {
        try {
            if(!isset($query)):
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun element trouvé"
                    ]
                );
            else:

                return DB::table('galerie_models')
                ->where('galerie_libelle','LIKE', '%'.$query.'%')
                ->orWhere('galerie_description','LIKE', '%'.$query.'%')
                ->orderByDesc('created_at')
                ->limit(15)
                ->get();

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



    public function filter_on_galerie($query)
    {
        try {
            if(!isset($query)):
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 404,
                        'message' => "Erreur!, Aucun element trouvé"
                    ]
                );
            else:

                return DB::table('galerie_models')
                ->where('galerie_libelle','LIKE', '%'.$query.'%')
                ->orWhere('galerie_description','LIKE', '%'.$query.'%')
                ->limit(5)
                ->get();

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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            if (empty($request->galerie_libelle)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "Le libellé de la galerie est obligatoire."
                    ]
                );
            endif;



            if (empty($request->galerie_img)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "L'image est obligatoire"
                    ]
                );
            endif;



            $this->galerie_img_url = UploadService::upload_galerie_image($request);

            if($this->galerie_img_url == "error"):
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "L'enregistrement de l'image a échoué."
                    ]
                );
            endif;
            $add_galerie = new GalerieModels();

            if($this->galerie_img_url != "file_not_found"):
                $add_galerie->galerie_img_url = $this->galerie_img_url;
            endif;
            $add_galerie->galerie_libelle = $request->galerie_libelle;
            $add_galerie->galerie_description = $request->galerie_description;

            $add_galerie->slug = CodeGenerator::generateRfk();
            if($add_galerie->save()) :
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok!, L'image a été enregistrée avec succès"
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement de l'image, veuillez réessayer!"
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


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        try {

            if (empty($request->galerie_libelle)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "Le libellé de la galerie est obligatoire."
                    ]
                );
            endif;


            $this->galerie_img_url = UploadService::upload_galerie_image($request);

            if($this->galerie_img_url == "error"):
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "L'enregistrement de l'image a échoué."
                    ]
                );
            endif;

            $update_galerie =  GalerieModels::where('slug',$slug)->first();


            if($this->galerie_img_url != "file_not_found"):
                $update_galerie->galerie_img_url = $this->galerie_img_url;
            endif;
            $update_galerie->galerie_libelle = $request->galerie_libelle;
            $update_galerie->galerie_description = $request->galerie_description;

            if($update_galerie->save()) :
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok!, L'image a été modifiée avec succès"
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification de l'image, veuillez réessayer!"
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

                DB::table('galerie_models')->where('slug', $slug)->delete();
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
