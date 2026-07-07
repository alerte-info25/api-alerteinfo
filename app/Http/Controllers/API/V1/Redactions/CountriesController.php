<?php

namespace App\Http\Controllers\API\V1\Redactions;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\UploadService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Redactions\CountriesModels;

class CountriesController extends Controller
{
    public $countries_flag;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return CountriesModels::all();
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

            if (empty($request->pays)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "Le pays est obligatoire"
                    ]
                );
            endif;

            if (empty($request->phone_code)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "Le code de téléphone du pays est obligatoire"
                    ]
                );
            endif;

            if (empty($request->currency)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "Le code de la monnaie du pays est obligatoire"
                    ]
                );
            endif;

            if (empty($request->iso_code)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "Le code du pays est obligatoire"
                    ]
                );
            endif;

            $this->countries_flag = UploadService::upload_countrie_flag($request);

            if($this->countries_flag == "error"):
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "L'enregistrement du pays a échoué."
                    ]
                );
            endif;

            $add_countrie = new CountriesModels();

            if($this->countries_flag != "file_not_found"):
                $add_countrie->flag = $this->countries_flag;
            endif;
            $add_countrie->pays = $request->pays;
            $add_countrie->phone_code = $request->phone_code;
            $add_countrie->currency = $request->currency;
            $add_countrie->iso_code = $request->iso_code;
            $add_countrie->slug = Str::uuid();

            if($add_countrie->save()) :
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok!, Le pays a été enregistré avec succès"
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement du pays, veuillez réessayer!"
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


        //return $request->all();
        try {

            if (empty($request->pays)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "Le pays est obligatoire"
                    ]
                );
            endif;

            

            if (empty($request->currency)) :
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => "302",
                        'message' => "Le code de la monnaie du pays est obligatoire"
                    ]
                );
            endif;

            

            $this->countries_flag = UploadService::upload_countrie_flag($request);

            if($this->countries_flag == "error"):
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "L'enregistrement du pays a échoué."
                    ]
                );
            endif;

            $update_countrie =  CountriesModels::where('id',$slug)->first();

            if($this->countries_flag != "file_not_found"):
                $update_countrie->flag = $this->countries_flag;
            endif;
            

            
            if($update_countrie->save()) :
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok!, Le pays a été modifié avec succès"
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification du pays, veuillez réessayer!"
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

                DB::table('countries_models')->where('slug', $slug)->delete();
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
