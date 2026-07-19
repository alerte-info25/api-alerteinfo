<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FooterSettingSeeder extends Seeder
{
    public function run(): void
    {
        // Une seule ligne dans la table (singleton)
        DB::table('footer_settings')->insertOrIgnore([
            'id'                    => 1,
            'description_1'         => "Le journal en ligne Quoi de Neuf est une publication de l'entreprise de presse ALERTE INFO, une Société à responsabilité limitée de droit ivoirien.",
            'description_2'         => "Le site web www.quoideneuf.info est régulièrement fourni en articles, par une équipe rédactionnelle autonome de la rédaction centrale du groupe ALERTE INFO.",
            'description_3'         => "Sauf autorisation, toute reproduction du contenu du journal en ligne Quoi de Neuf est formellement interdite.",
            'phones'                => json_encode([
                ['label' => 'ABIDJAN',      'number' => '+2250102500320'],
                ['label' => 'ABIDJAN',      'number' => '+2250709620606'],
                ['label' => 'OUAGADOUGOU',  'number' => '+22678600095'],
                ['label' => 'OUAGADOUGOU',  'number' => '+22678607420'],
                ['label' => 'OUAGADOUGOU',  'number' => '+22664540192'],
            ]),
            'email_direction'       => 'direction@alerte-info.net',
            'email_redaction'       => 'redaction@alerte-info.net',
            'address_abidjan_city'  => 'MARCORY, QUARTIER BROU FULGENCE',
            'address_abidjan_detail'=> '30 rue de Korhogo, Lot 324, parcelle 325, Ilot 7, zone D',
            'address_ouaga_city'    => 'OUAGADOUGOU, Avenue du Liptako Gourma',
            'address_ouaga_detail'  => "2e étage de l'immeuble situé en face de Ciné Burkina",
            'facebook_url'          => 'https://www.facebook.com/profile.php?id=61558709453752&locale=fr_FR',
            'youtube_url'           => 'https://www.youtube.com/@AlerteInfoNews',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }
}
