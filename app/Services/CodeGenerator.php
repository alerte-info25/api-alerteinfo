<?php
namespace App\Services;


use DateTime;
use App\Models\Clubs;
use App\Models\Ligue;
use App\Models\Federation;
use Illuminate\Support\Facades\DB;

class CodeGenerator

{
    public static function generateMatricule()
    {
        $lenght= 6;
        $keys = substr(str_shuffle(
            str_repeat($x = '0987654321234567890', ceil($lenght / strlen($x)))
        ), 3, $lenght);

        return $keys."AI";
    }

    public static function generatePassword()
    {
        $lenght= 8;
        $keys = substr(str_shuffle(
            str_repeat($x = 'AUTHORIZA1234567890TIONPASSWORD', ceil($lenght / strlen($x)))
        ), 3, $lenght);
        return $keys . '-' . date('Hs', strtotime(now()));
    }


    public static function generateRfk()
    {
        $length = 50;
        $keys = substr(str_shuffle(
            str_repeat($x = '1234567890', ceil($length / strlen($x)))
        ), 3, $length);

        // Ajout de l'heure, minutes et secondes au format His (HeureMinuteSeconde)
        $timeStamp = date('His', strtotime(now()));

        return $timeStamp . '-' . $keys;
    }

    public static function generatePermissionCode()
    {
        return "PERM" ."-".  date('dmY-His', strtotime(now()));
    }

    public static function generateMissionCode()
    {
        return "MISS" ."-".  date('dmY-His', strtotime(now()));
    }

    public static function generateCongeCode()
    {
        return "CONG" ."-".  date('dmY-His', strtotime(now()));
    }


    public static function generateEmployeCode()
    {

        $verify_last_code = DB::table('employes_models')
        ->orderBy('id', 'desc')
        ->value('employe_ref');

        if($verify_last_code == null):
            return "EMP"."-".'00001';
        else:

//            $first_str = strstr($verify_last_code, '-');
//
//            $get_code_number = substr($first_str, 4);
//
//            $incrementedNumber = str_pad($get_code_number + 1, strlen($get_code_number), '0', STR_PAD_LEFT);
//
//            return 'EMP'.'-'.$incrementedNumber;




            $first_str = strstr($verify_last_code, '-');

            $get_code_number = substr($first_str, 1);

//            return $get_code_number;
            $incrementedNumber = str_pad($get_code_number + 1, strlen($get_code_number), '0', STR_PAD_LEFT);

//            return $incrementedNumber;
            return 'EMP'.'-'.$incrementedNumber;
        endif;
    }

    public static function generateDosCode()
    {
        return "DOS" ."-".  date('dmY-His', strtotime(now()));
    }


    public static function generateSlugCode()
    {
        $lenght= 50;
        $keys = substr(str_shuffle(
            str_repeat($x = '1234567890', ceil($lenght / strlen($x)))
        ), 3, $lenght);

        return $keys;
    }


    public static function generateSlugCodeInDiffusion()
    {
        $lenght= 150;
        $keys = substr(str_shuffle(
            str_repeat($x = '1234567890', ceil($lenght / strlen($x)))
        ), 3, $lenght);

        return $keys;
    }



    public static function generateAbonnementCodeUnique()
    {
        $lenght= 10;
        $keys = substr(str_shuffle(
            str_repeat($x = 'ABONNEMENTCODEEUNIQUE1234567890', ceil($lenght / strlen($x)))
        ), 3, $lenght);

        return "ABN".".".$keys.".". date('dmY-His', strtotime(now()));
    }

}
