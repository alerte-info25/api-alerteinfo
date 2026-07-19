<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\AbonnementsWebModels\AbonnementWebForfaitsModels;

class ForfaitsAbonnennementWebSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //AbonnementWebForfaitsModels::truncate();
        $data = [
            [
                'category_code' => '20250319143012-IYO5XL',
                'forfait' => 'JOURNEE',
                'montant' => 2900,
                'duree' => 1,
                'status' => 'PREMIUM',
                'slug' => Str::lower(Str::random(30)),
            ],
            [
                'category_code' => '20250319143012-IYO5XL',
                'forfait' => 'SEMAINE',
                'montant' => 19900,
                'duree' => 7,
                'status' => 'PREMIUM',
                'slug' => Str::lower(Str::random(30)),
            ]
            ,
            [
                'category_code' => '20250319143012-IYO5XL',
                'forfait' => 'MOIS',
                'montant' => 39900,
                'duree' => 30,
                'status' => 'PREMIUM',
                'slug' => Str::lower(Str::random(30)),
            ]
            ,
            [
                'category_code' => '20250319143012-IYO5XL',
                'forfait' => 'TRIMESTRE',
                'montant' => 159900,
                'duree' => 90,
                'status' => 'PREMIUM',
                'slug' => Str::lower(Str::random(30)),
            ]
            ,
            [
                'category_code' => '20250319143012-IYO5XL',
                'forfait' => 'ANNUEL',
                'montant' => 599000,
                'duree' => 365,
                'status' => 'PREMIUM',
                'slug' => Str::lower(Str::random(30)),
            ]
        ];

        foreach ($data as $item) {
            AbonnementWebForfaitsModels::create($item);
        }
    }
}
