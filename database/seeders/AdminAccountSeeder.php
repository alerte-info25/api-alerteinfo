<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\UserAccounts\AdminAccountModel;
use App\Services\MailSenderServices\MailSenderService;
use App\Services\CodeGeneratorServices\CodeGeneratorService;

class AdminAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdminAccountModel::truncate();

        $accountCodeUnique = CodeGeneratorService::generateDefaultCodeUnique(
            'admin_account_models',
            'account_code_unique',
            'ADM-ACC'
        ); // generate unique account code

        $password = "broudev@2024";

        $adminAccount = AdminAccountModel::create([
            "account_code_unique" => $accountCodeUnique,
            "role_code_unique" => "ADM-ROLE25.000001",
            "first_name" => "Admin",
            "last_name" => "Admin",
            "email" => "brou4859@gmail.com",
            "phone" => "0655555555",
            "photo" => "Aucun",
            "password" => Hash::make($password),
            "status" => 1,
            "slug" => Str::uuid(),
        ]);

        MailSenderService::srv_sendAccountCreationNotification($adminAccount,$password);
    }
}
