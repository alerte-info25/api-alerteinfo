<?php

namespace App\Http\Controllers\API\V1\DiffusionSms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DiffusionSms\GestionOperatorModels;

class GestionOperatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return GestionOperatorModels::all();
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

            if(empty($request->operator_name)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs opérateur est obligatoire"
                    ]
                );
            endif;
            
            if(empty($request->country)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs pays est obligatoire"
                    ]
                );
            endif;
            
            $add_data = new GestionOperatorModels();

            $add_data->operator_name = $request->operator_name;
            $add_data->status = 1;
            $add_data->slug = CodeGenerator::generateSlugCodeInDiffusion();


            if($add_data->save()):
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok ! L'opérateur a été enregistré avec succès. "
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement de opérateur, veuillez réessayer!"
                    ]
                );
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
    
    
    /**
     * Store a newly created resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            if(isset($id)):

                if(empty($request->operator_name)):
                    return response()->json(
                        [
                            'code' => 302,
                            'status' => 'erreur',
                            'message' => "Erreur! Le champs opérateur est obligatoire"
                        ]
                    );
                endif;
            
                if(empty($request->country)):
                    return response()->json(
                        [
                            'code' => 302,
                            'status' => 'erreur',
                            'message' => "Erreur! Le champs pays est obligatoire"
                        ]
                    );
                endif;
                
                $add_data = GestionOperatorModels::Where('id', $id)->first();

                $add_data->operator_name = $request->operator_name;
                $add_data->country = $request->country;
                $add_data->status = 1;
                $add_data->slug = CodeGenerator::generateSlugCodeInDiffusion();


                if($add_data->save()):
                    return response()->json(
                        [
                            'status' => 'succès',
                            'code' => 200,
                            'message' => "Ok ! L'opérateur a été modifié avec succès. "
                        ]
                    );
                else:
                    return response()->json(
                        [
                            'status' => 'erreur',
                            'code' => 300,
                            'message' => "Erreur ! Échec de la modification de opérateur, veuillez réessayer!"
                        ]
                    );
                endif;
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec opérateur non trouvé, veuillez réessayer!"
                    ]
                );
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
    
    public function destroy(string $id)
    {
        try {
            
           if (!$id) :
               return response()->json(
                   [
                       'status' => 'error',
                       'code' => 404,
                       'message' => "Erreur!, Aucun element trouvé"
                   ]
               );
           else :

               GestionOperatorModels::Where('id', $id)->delete();
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
