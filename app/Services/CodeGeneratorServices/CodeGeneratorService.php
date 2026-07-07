<?php

namespace App\Services\CodeGeneratorServices;

use App\Models\User;
use Illuminate\Support\Str;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Countrie\CountrieModels;
use App\Models\EmployeeManager\EmployeePersonalInformationsModels;

class CodeGeneratorService
{

    public static function getAuthenticatedGuard()
    {
        if (auth('admin')->check()) {
            return 'admin';
        } elseif (auth('employee')->check()) {
            return 'employee';
        }
        return null; // Aucun utilisateur connecté
    }
    // generate code authorizations
    public static function generateCodeAuthorization(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $length = 6;

        $code = substr(str_shuffle($characters), 0, $length);
        return 'BN-' . strtoupper($code);
    }
    public static function generateSlugCode(): UuidInterface
    {
        return Str::uuid7();
    }

    // generate user account code unique
    public static function generateUserAccountCodeUnique()
    {
        return "USR" . substr(date('Y', strtotime(now())), -2) . '.' . date('mdYHis', strtotime(now()));
    }

    // generate cv code unique
    public static function generateCvCodeUnique()
    {
        return "CV" . substr(date('Y', strtotime(now())), -2) . '.' . date('mdYHis', strtotime(now()));
    }

    // gereate photo name
    public static function generatePhotoName()
    {
        $guard = self::getAuthenticatedGuard();
        return auth($guard)->user()->first_name . "_" . auth($guard)->user()->last_name . "_" . substr(date('Y', strtotime(now())), -2) . '_' . date('mdHis', strtotime(now()));
    }

    // generate language code unique
    public static function generateLanguageCodeUnique()
    {
        return "LANG" . substr(date('Y', strtotime(now())), -2) . '.' . date('mdYHis', strtotime(now()));
    }

    // generate project code unique
    // generate project code unique
    public static function generateProjectCodeUnique()
    {
        return "PROJ" . substr(date('Y', strtotime(now())), -2) . '.' . date('mdYHis', strtotime(now()));
    }

    // generate language code unique
    // generate experience code unique
    public static function generateExperienceCodeUnique()
    {
        return "EXP" . substr(date('Y', strtotime(now())), -2) . '.' . date('mdYHis', strtotime(now()));
    }

    // generate education code unique
    // generate skill code unique
    public static function generateSkillCodeUnique()
    {
        return "SKL" . substr(date('Y', strtotime(now())), -2) . '.' . date('mdYHis', strtotime(now()));
    }

    // generate  admin account code unique
    public static function generateAdminAccountCodeUnique()
    {
        return "ADM" . substr(date('Y', strtotime(now())), -2) . '.' . date('mdYHis', strtotime(now()));
    }


    // generate password
    public static function generatePassword()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return strtoupper($randomString);
    }


    // generate password hash

    /**
     * Summary of generatePasswordHash
     * @param mixed $password
     * @return string
     */
    public static function generatePasswordHash($password): string
    {
        return Hash::make($password);
    }



    // check if password is correct
    public static function checkPassword($password, $hashedPassword): bool
    {
        return Hash::check($password, $hashedPassword);
    }

    /**
     * Summary of generateDefaultCodeUnique
     * @param mixed $table
     * @param mixed $tableCodeUnique
     * @param mixed $abreviation
     * @return string
     */
    public static function generateDefaultCodeUnique($table, $tableCodeUnique, $abreviation): string
    {
        // Récupérer le dernier code généré
        $lastCode = DB::table($table)
            ->orderByDesc('id')
            ->value($tableCodeUnique);

        // Année sur 2 chiffres (ex: 24 pour 2024)
        $yearSuffix = substr(date('Y'), -2);



        // Si aucun code précédent n'existe
        if (is_null($lastCode)) {
            return $abreviation . $yearSuffix . '.000001';
        }

        // Construction du motif avec échappement pour sécurité
        $pattern = '/^' . preg_quote($abreviation . $yearSuffix . '.', '/') . '(\d{6})([A-Z]*)$/';

        // Extraire les parties numériques et alphabétiques du dernier code
        if (preg_match($pattern, $lastCode, $matches)) {
            $lastNumber = (int) $matches[1];
            $lastLetter = $matches[2] ?? '';
        } else {
            // Si le format du dernier code est inattendu, recommencer proprement
            return $abreviation . $yearSuffix . '.000001';
        }

        // Incrémentation du numéro
        $newNumber = $lastNumber + 1;

        if ($newNumber > 999999) {
            $newNumber = 1; // Recommencer à 1

            // Gérer l'incrémentation des lettres
            $newLetter = empty($lastLetter) ? 'A' : self::incrementLetter($lastLetter);
        } else {
            $newLetter = $lastLetter;
        }

        // Construction du code final
        $code = $abreviation . $yearSuffix . '.' . str_pad($newNumber, 6, '0', STR_PAD_LEFT) . $newLetter;

        return $code;
    }





    // Fonction pour incrémenter les lettres (A → B → ... → Z → AA → AB → ...)
    private static function incrementLetter($letter)
    {
        $length = strlen($letter);
        for ($i = $length - 1; $i >= 0; $i--) {
            if ($letter[$i] !== 'Z') {
                $letter[$i] = chr(ord($letter[$i]) + 1);
                return $letter;
            }
            $letter[$i] = 'A';
        }
        return 'A' . $letter;
    }
}
