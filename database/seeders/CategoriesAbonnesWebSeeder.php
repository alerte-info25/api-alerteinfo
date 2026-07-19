<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Nette\Utils\Random;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\AbonnesWebModels\CategoriesAbonnesWebModels;

class CategoriesAbonnesWebSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = Carbon::now()->format('YmdHis'); // Date complète (année-mois-jour-heure-minute-seconde)
        $randomString = Str::upper(Str::random(6));

        CategoriesAbonnesWebModels::truncate();
        // generate category_code
        $categories = [
            [
                'category_code' => Carbon::now()->format('YmdHis') . '-' .Str::upper(Str::random(6)),
                'categorie' => 'Usage personnel',
                'can_copy' => 0,
                'can_share' => 0,
                'can_read' => true,
                'can_download' => 0,
                'slug' => Str::lower(Str::random(30))
            ],
            [
                'category_code' => Carbon::now()->format('YmdHis') . '-' .Str::upper(Str::random(6)),
                'categorie' => 'Usage commercial',
                'can_copy' => 1,
                'can_share' => 1,
                'can_read' =>  1,
                'can_download' => 1
                ,'slug' => Str::lower(Str::random(30))
            ]
        ];
        foreach ($categories as $category) {
            CategoriesAbonnesWebModels::create($category);
        }
    }
}
