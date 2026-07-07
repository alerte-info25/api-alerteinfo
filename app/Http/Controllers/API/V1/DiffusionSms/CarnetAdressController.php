<?php

namespace App\Http\Controllers\API\V1\DiffusionSms;

use Illuminate\Http\Request;
use App\Services\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\DiffusionSms\CarnetAdressModels;
use App\Models\DiffusionSms\GroupCarnetAdressModels;
use App\Models\DiffusionSms\SingleCarnetAdressModels;

class CarnetAdressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index_single_contact()
    {
        try {
            return SingleCarnetAdressModels::all();
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
     * Display a listing of the resource.
     */
    public function index_group_contact()
    {
        try {
            return GroupCarnetAdressModels::all();
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
    public function store_single_contact(Request $request)
    {
        try {

            if(empty($request->fname)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs nom est obligatoire"
                    ]
                );
            endif;


            if(empty($request->lname)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs prénoms est obligatoire"
                    ]
                );
            endif;

            if(empty($request->email)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs email est obligatoire"
                    ]
                );
            endif;

            if(empty($request->contact)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs contact est obligatoire"
                    ]
                );
            endif;

            $add_data = new SingleCarnetAdressModels();

            $add_data->fname = $request->fname;
            $add_data->lname = $request->lname;
            $add_data->email = $request->email;
            $add_data->contact = $request->contact;
            $add_data->status = 1;
            $add_data->slug = CodeGenerator::generateSlugCodeInDiffusion();


            if($add_data->save()):

                //$notifiction = "Bonjour cher.". " " .$store_employe_info->first_name ." bienvenue à l'Office Ivoirien de la Propriété Intellectuelle (OIPI) " ;
                //Mail::to($store_employe_info->email)->send(new Notifications($notifiction));

                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok ! Le contact a été enregistré avec succès. "
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement du contact, veuillez réessayer!"
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
    public function store_group_contact(Request $request)
    {
        try {

            if(empty($request->operator_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs opérateur est obligatoire"
                    ]
                );
            endif;


            if(empty($request->libelle)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs nom du groupe est obligatoire"
                    ]
                );
            endif;

            if(empty($request->list_contact)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Vous devez ajouter des contact au groupe"
                    ]
                );
            endif;

            $add_data = new GroupCarnetAdressModels();

            $add_data->operator_id = $request->operator_id;
            $add_data->group_name = $request->libelle;
            $add_data->list_contact = implode(',', $request->list_contact);
            $add_data->status = 1;
            $add_data->slug = CodeGenerator::generateSlugCodeInDiffusion();


            if($add_data->save()):

                //$notifiction = "Bonjour cher.". " " .$store_employe_info->first_name ." bienvenue à l'Office Ivoirien de la Propriété Intellectuelle (OIPI) " ;
                //Mail::to($store_employe_info->email)->send(new Notifications($notifiction));

                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok ! Le groupe a été enregistré avec succès. "
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de l'enregistrement du groupe, veuillez réessayer!"
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
    public function store_document_contact(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update_single_contact(Request $request, string $id)
    {
        try {

            if(empty($request->fname)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs nom est obligatoire"
                    ]
                );
            endif;


            if(empty($request->lname)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs prénoms est obligatoire"
                    ]
                );
            endif;

            if(empty($request->email)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs email est obligatoire"
                    ]
                );
            endif;

            if(empty($request->contact)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs contact est obligatoire"
                    ]
                );
            endif;

            $add_data = SingleCarnetAdressModels::Where('id', $id)->first();

            $add_data->fname = $request->fname;
            $add_data->lname = $request->lname;
            $add_data->email = $request->email;
            $add_data->contact = $request->contact;


            if($add_data->save()):

                //$notifiction = "Bonjour cher.". " " .$store_employe_info->first_name ." bienvenue à l'Office Ivoirien de la Propriété Intellectuelle (OIPI) " ;
                //Mail::to($store_employe_info->email)->send(new Notifications($notifiction));

                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok ! Le contact a été modifié avec succès. "
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modification du contact, veuillez réessayer!"
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
     * Update the specified resource in storage.
     */
    public function update_group_contact(Request $request, string $id)
    {
        try {

            if(empty($request->operator_id)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs opérateur est obligatoire"
                    ]
                );
            endif;


            if(empty($request->libelle)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Le champs nom du groupe est obligatoire"
                    ]
                );
            endif;

            if(empty($request->list_contact)):
                return response()->json(
                    [
                        'code' => 302,
                        'status' => 'erreur',
                        'message' => "Erreur! Vous devez ajouter des contact au groupe"
                    ]
                );
            endif;

            $add_data = GroupCarnetAdressModels::Where('id', $id)->first();

            $add_data->operator_id = $request->operator_id;
            $add_data->group_name = $request->libelle;
            $add_data->list_contact = implode(',', $request->list_contact);


            if($add_data->save()):

                //$notifiction = "Bonjour cher.". " " .$store_employe_info->first_name ." bienvenue à l'Office Ivoirien de la Propriété Intellectuelle (OIPI) " ;
                //Mail::to($store_employe_info->email)->send(new Notifications($notifiction));

                return response()->json(
                    [
                        'status' => 'succès',
                        'code' => 200,
                        'message' => "Ok ! Le groupe a été modifié avec succès. "
                    ]
                );
            else:
                return response()->json(
                    [
                        'status' => 'erreur',
                        'code' => 300,
                        'message' => "Erreur ! Échec de la modifié du groupe, veuillez réessayer!"
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
     * Remove the specified resource from storage.
     */
    public function destroy_single_contact(string $id)
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

               SingleCarnetAdressModels::Where('id', $id)->delete();
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy_group_contact(string $id)
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

               GroupCarnetAdressModels::Where('id', $id)->delete();
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
