<?php

namespace App\Http\Controllers\API\V1\Banners;

use App\Http\Controllers\Controller;
use App\Models\Banners\BannerModels;
use App\Services\CodeGenerator;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BannerController extends Controller
{
    public $banner_image_url;

    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['get_728X90','get_1920X309','get_1200X1500']]);
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return DB::table('banner_models')->limit(50)->get();
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


    public function get_728X90()
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


    public function get_1920X309()
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

    public function get_1200X1500()
    {

        try {
            return DB::table('banner_models')->where('libelle',"1200X1500")
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



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            if (empty($request->libelle)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "La dimension de la bannière est obligatoire."
                    ]
                );
            endif;



            if (empty($request->banner_image)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "La bannière est obligatoire"
                    ]
                );
            endif;

            if (empty($request->plateform)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "La plateforme est obligatoire"
                    ]
                );
            endif;



            $this->banner_image_url = UploadService::upload_banner_image($request);

            if($this->banner_image_url == "error"):
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "L'enregistrement de la bannière a échoué."
                    ]
                );
            endif;

            
            $add_banner = new BannerModels();

            if($this->banner_image_url != "file_not_found"):
                $add_banner->banner_image = $this->banner_image_url;
            endif;
            $add_banner->libelle = $request->libelle;
            $add_banner->img_url = $request->img_url;
            $add_banner->plateform = $request->plateform;

            $add_banner->slug = CodeGenerator::generateRfk();

            if($add_banner->save()) :
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok!, La bannière a été enregistrée avec succès"
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

            if (empty($request->libelle)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "La dimension de la bannière est obligatoire."
                    ]
                );
            endif;



            $this->banner_image_url = UploadService::upload_banner_image($request);

            if($this->banner_image_url == "error"):
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "L'enregistrement de la bannière a échoué."
                    ]
                );
            endif;




            $update_banner =  BannerModels::where('slug',$slug)->first();

            if($this->banner_image_url != "file_not_found"):
                $update_banner->banner_image = $this->banner_image_url;
            endif;
            $update_banner->libelle = $request->libelle;
            $update_banner->img_url = $request->img_url;
            $update_banner->plateform = $request->plateform;

            if($update_banner->save()) :
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok!, La bannière a été modifiée avec succès"
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification de la bannière, veuillez réessayer!"
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

                DB::table('banner_models')->where('slug', $slug)->delete();
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


    public static function enable_or_disable_banner($slug)
    {

        try {
            if(isset($slug)):


                $lock_banners = DB::table('banner_models')->where('slug',$slug)->value('status');

                if($lock_banners == 0):
                    $banners = DB::table('banner_models')->where('slug',$slug)->update(['status' => 1]);
                    return response()->json(
                        [
                            'status' => 'success',
                            'code' => 200,
                            'message' => "Ok ! Activation éffectuée avec succès"
                        ]
                    );
                    
                endif;
                if($lock_banners == 1):
                    $banners = DB::table('banner_models')->where('slug',$slug)->update(['status' => 0]);

                    return response()->json(
                        [
                            'status' => 'success',
                            'code' => 200,
                            'message' => "Ok ! Désactivation éffectuée avec succès"
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
}
