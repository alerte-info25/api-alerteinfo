<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\UserRole\UserRoleModels;
use App\Models\UserAccounts\UserRoleModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Services\CodeGeneratorServices\CodeGeneratorService;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        UserRoleModel::truncate();

        $roleCodeUnique = CodeGeneratorService::generateDefaultCodeUnique(
            'user_role_models',
            'role_code_unique',
            'ADM-ROLE'
        ); // generate unique role code



        $roleListe = [
            [
                "role_code_unique" => $roleCodeUnique,
                "role_name" => "Administrateur",
                "slug" => Str::uuid(),
                "created_at" => now(),
                "updated_at"=> now(),
            ],

        ];

        foreach ($roleListe as $role) {
            UserRoleModel::create($role);
        }
    }
}
