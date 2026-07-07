<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService

{
    public static function get()
    {
        try {
            return User::join('roles','users.role_id', '=', 'roles.id')
            ->select('roles.role', 'users.*')
            ->where('type','totem')
            ->get();
        } catch (\Throwable $e) {
            return "bad_request";
        }

    }

    public static function get_user_active()
    {
        try {
            return User::join('roles','users.role_id', '=', 'roles.id')
            ->select('roles.role', 'users.*')
            ->where('type','totem')
            ->where('status',1)
            ->first();
        } catch (\Throwable $e) {
            return "bad_request";
        }

    }

    public static function add($data)
    {

        try {
            if(isset($data)):

                $lenght= 100;
                $keys = substr(str_shuffle(
                    str_repeat($x = 'abcdefghqoujpzy1234567890', ceil($lenght / strlen($x)))
                ), 3, $lenght);


                $checkUser = DB::table('users')->where('email',$data->email)
                    ->where('type',$data->type)->first();
                if($checkUser != null):
                    return "already";
                endif;

                $add_user = new User();

                $add_user->full_name = $data->full_name;
                $add_user->email = $data->email;
                $add_user->role_id = $data->role_id;
                $add_user->type = $data->type;
                $add_user->password = password_hash($data->password, PASSWORD_DEFAULT);
                $add_user->rfk = $keys;

                if($add_user->save()):
                    $history = HistoriqueServices::manager("Ajout d'un utilisateur #ID".$add_user->id, $data->author);
                    return $history;
                else:
                    return "error";
                endif;
            else:
                return "error";
            endif;
        } catch (\Throwable $e) {
            return "bad_request";
        }


    }

    public static function edit($id)
    {

        try {
            if(isset($id)):

                $edit_users = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->select('roles.role', 'users.*')
                ->where('users.id',$id)->first();

                if(!$edit_users):
                    return false;
                else:
                    return $edit_users;
                endif;
            endif;
        } catch (\Throwable $e) {
            return "bad_request";
        }

    }

    public static function update($data,$id)
    {
        try {
            if(isset($data)):

                $lenght= 100;
                $keys = substr(str_shuffle(
                    str_repeat($x = 'abcdefghqoujpzy1234567890', ceil($lenght / strlen($x)))
                ), 3, $lenght);


                $update_user = User::where('id',$id)->first();
                //dd($update_user); die();
                $update_user->full_name = $data->full_name;
                $update_user->email = $data->email;
                $update_user->type = $data->type;
                $update_user->role_id = $data->role_id;
                $update_user->rfk = $keys;

                if($update_user->save()):
                    $history = HistoriqueServices::manager("Modification d'un utilisateur #ID".$update_user->id, $data->author);
                    return $history;
                else:
                    return "error";
                endif;
            endif;
        } catch (\Throwable $e) {
            return "bad_request";
        }

    }

    public static function destroy($id,$author)
    {
        try {
            if(isset($id)):
                HistoriqueServices::manager("Suppression d'un utilisateur #ID".$id, $author);
                $delete_user = DB::table('users')->where('id',$id)->delete();

                if(!$delete_user):
                    return "error";
                else:
                    return "success";
                endif;
            endif;
        } catch (\Throwable $e) {
            return "bad_request";
        }

    }


    public static function find_user_type($email)
    {
        try {
            $users_type = User::where('email', $email)->where('type',"totem")->first();
            if($users_type == null):
                return "error";
            endif;
        } catch (\Throwable $e) {
            return "bad_request";
        }

    }





    public static function check_user($email)
    {

        try {
            $users = User::where('email', $email)->where('type',"totem")->first();
            if($users != null):
                return $users;
            else:
                return "error";
            endif;
        } catch (\Throwable $e) {
            return "bad_request";
        }



    }



    public static function check_matricule($data)
    {

        try {
            $admin = DB::table('compte_admins')->where('matricule', $data->matricule)->first();
            $employe = DB::table('compte_employes')->where('matricule', $data->matricule)->first();
            $redaction = DB::table('compte_redactions')->where('matricule', $data->matricule)->first();
            $quoideneuf = DB::table('compte_quoi_de_neufs')->where('matricule', $data->matricule)->first();

            //return $admin;
            if($admin == null && $employe == null && $redaction == null && $quoideneuf == null):
                return "not_found";
            endif;
            if(

                ($employe != null && $redaction != null && $quoideneuf != null && $admin != null)
            ):
                return "is_corrupted";
            endif;

            if($admin != null):
                return "is_admin";
            endif;

            if($employe != null):
                return "is_employe";
            endif;

            if($redaction != null && $quoideneuf != null):
                return "is_redaction";
            endif;
            if($redaction != null):
                return "is_redaction";
            endif;

            if($quoideneuf != null):
                return "is_quoideneuf";
            endif;

        } catch (\Throwable $e) {
            return $e;
        }



    }



    public static function update_user_connexion($id)
    {
        try {
            DB::table('users')->where('id', $id)->update((
                ['is_connected' => 1]
            ));
        } catch (\Throwable $e) {
            return "bad_request";
        }

    }
}
