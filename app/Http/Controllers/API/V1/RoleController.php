<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Role;
use App\Services\CodeGenerator;
use Illuminate\Http\Request;
use App\Services\RoleService;
use App\Services\FlashMessages;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    
    public function get()
    {
        try {
            return Role::all();
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

    public function add(Request $request)
    {

        try {
            if (empty($request->role)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "Le role est obligatoire"
                    ]
                );
            endif;

            $add_role = new Role();
            $add_role->role = $request->role;
            $add_role->slug = CodeGenerator::generateRfk();

            if($add_role->save()) :
                return response()->json(
                    [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Ok!, Le rôle a été enregistré avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'error',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement du rôle, veuillez réessayer!"
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




    public function update(Request $request,$slug)
    {
        try {

            if (empty($request->role)) :
                return response()->json(
                    [
                        'code' => "302",
                        'message' => "La rôle est obligatoire"
                    ]
                );
            endif;

            $update_role = Role::where('slug',$slug)->first();
            $update_role->role = $request->role;

            if($update_role->save()) :
                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => 'Ok!, La rôle a été modifié avec succès'
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification du rôle, veuillez réessayer!"
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

    public function destroy($slug)
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

                DB::table('roles')->where('slug', $slug)->delete();
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
