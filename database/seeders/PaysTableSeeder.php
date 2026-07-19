<?php

namespace Database\Seeders;

use App\Models\Pays;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Redactions\CountriesModels;

class PaysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CountriesModels::truncate();

        $data = [
            [
                'pays' => "Côte d'Ivoire",
                'flag' => "",
                'iso_code' => "CI",
                'phone_code' => "225",
                'currency' => "XOF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],
            [
                'pays' => "Burkina Faso",
                'flag' => "",
                'iso_code' => "BF",
                'phone_code' => "226",
                'currency' => "XOF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Mali",
                'flag' => "",
                'iso_code' => "ML",
                'phone_code' => "223",
                'currency' => "XOF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Chad",
                'flag' => "",
                'iso_code' => "TD",
                'phone_code' => "235",
                'currency' => "XAF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "République Centrafricaine",
                'flag' => "",
                'iso_code' => "CF",
                'phone_code' => "236",
                'currency' => "XAF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Guinée Equatoriale",
                'flag' => "",
                'iso_code' => "GQ",
                'phone_code' => "240",
                'currency' => "XAF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Guinée",
                'flag' => "",
                'iso_code' => "GN",
                'phone_code' => "224",
                'currency' => "GNF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Comore",
                'flag' => "",
                'iso_code' => "KM",
                'phone_code' => "269",
                'currency' => "KMF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Ghana",
                'flag' => "",
                'iso_code' => "GH",
                'phone_code' => "233",
                'currency' => "GHS",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "La Gambie",
                'flag' => "",
                'iso_code' => "GM",
                'phone_code' => "220",
                'currency' => "GMD",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Nigeria",
                'flag' => "",
                'iso_code' => "NG",
                'phone_code' => "234",
                'currency' => "NGN",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Niger",
                'flag' => "",
                'iso_code' => "NE",
                'phone_code' => "227",
                'currency' => "XOF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Sierra Leone",
                'flag' => "",
                'iso_code' => "SL",
                'phone_code' => "232",
                'currency' => "SLL",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Mauritanie",
                'flag' => "",
                'iso_code' => "MR",
                'phone_code' => "222",
                'currency' => "MRO",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Mauritius",
                'flag' => "",
                'iso_code' => "MU",
                'phone_code' => "230",
                'currency' => "MUR",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Zimbabwe",
                'flag' => "",
                'iso_code' => "ZW",
                'phone_code' => "263",
                'currency' => "ZWL",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Mozambique",
                'flag' => "",
                'iso_code' => "MZ",
                'phone_code' => "258",
                'currency' => "MZN",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Zambie",
                'flag' => "",
                'iso_code' => "ZM",
                'phone_code' => "260",
                'currency' => "ZMW",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Soudan du sud",
                'flag' => "",
                'iso_code' => "SS",
                'phone_code' => "211",
                'currency' => "SSP",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Soudan",
                'flag' => "",
                'iso_code' => "SD",
                'phone_code' => "249",
                'currency' => "SDG",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Algerie",
                'flag' => "",
                'iso_code' => "DZ",
                'phone_code' => "213",
                'currency' => "DZD",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Eritrea",
                'flag' => "",
                'iso_code' => "ER",
                'phone_code' => "291",
                'currency' => "ERN",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Angola",
                'flag' => "",
                'iso_code' => "AO",
                'phone_code' => "244",
                'currency' => "AOA",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Benin",
                'flag' => "",
                'iso_code' => "BJ",
                'phone_code' => "229",
                'currency' => "XOF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Botswana",
                'flag' => "",
                'iso_code' => "BW",
                'phone_code' => "267",
                'currency' => "BWP",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Burundi",
                'flag' => "",
                'iso_code' => "BI",
                'phone_code' => "257",
                'currency' => "BIF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Cameroon",
                'flag' => "",
                'iso_code' => "CM",
                'phone_code' => "237",
                'currency' => "XAF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Cape Vert",
                'flag' => "",
                'iso_code' => "CV",
                'phone_code' => "238",
                'currency' => "CVE",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Congo",
                'flag' => "",
                'iso_code' => "CG",
                'phone_code' => "242",
                'currency' => "XAF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "RD Congo",
                'flag' => "",
                'iso_code' => "CD",
                'phone_code' => "243",
                'currency' => "CDF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Djibouti",
                'flag' => "",
                'iso_code' => "DJ",
                'phone_code' => "253",
                'currency' => "DJF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Egypt",
                'flag' => "",
                'iso_code' => "EG",
                'phone_code' => "20",
                'currency' => "EGP",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Ethiopie",
                'flag' => "",
                'iso_code' => "ET",
                'phone_code' => "251",
                'currency' => "ETB",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Gabon",
                'flag' => "",
                'iso_code' => "GA",
                'phone_code' => "241",
                'currency' => "XAF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Guinee-Bissau",
                'flag' => "",
                'iso_code' => "GW",
                'phone_code' => "245",
                'currency' => "XOF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Kenya",
                'flag' => "",
                'iso_code' => "KE",
                'phone_code' => "254",
                'currency' => "KES",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Lesotho",
                'flag' => "",
                'iso_code' => "LS",
                'phone_code' => "266",
                'currency' => "LSL",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Liberia",
                'flag' => "",
                'iso_code' => "LR",
                'phone_code' => "231",
                'currency' => "LRD",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Libye",
                'flag' => "",
                'iso_code' => "LY",
                'phone_code' => "218",
                'currency' => "LYD",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Madagascar",
                'flag' => "",
                'iso_code' => "MG",
                'phone_code' => "261",
                'currency' => "MGA",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Malawi",
                'flag' => "",
                'iso_code' => "MW",
                'phone_code' => "265",
                'currency' => "MWK",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Maroc",
                'flag' => "",
                'iso_code' => "MA",
                'phone_code' => "212",
                'currency' => "MAD",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Mozambique",
                'flag' => "",
                'iso_code' => "MZ",
                'phone_code' => "258",
                'currency' => "MZN",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Namibie",
                'flag' => "",
                'iso_code' => "NA",
                'phone_code' => "264",
                'currency' => "NAD",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Rwanda",
                'flag' => "",
                'iso_code' => "RW",
                'phone_code' => "250",
                'currency' => "RWF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Sao Tomé et principe",
                'flag' => "",
                'iso_code' => "ST",
                'phone_code' => "239",
                'currency' => "STD",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Senegal",
                'flag' => "",
                'iso_code' => "SN",
                'phone_code' => "221",
                'currency' => "XOF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Somalia",
                'flag' => "",
                'iso_code' => "SO",
                'phone_code' => "252",
                'currency' => "SOS",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Tanzanie",
                'flag' => "",
                'iso_code' => "TZ",
                'phone_code' => "255",
                'currency' => "TZS",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Afrique du sud",
                'flag' => "",
                'iso_code' => "ZA",
                'phone_code' => "27",
                'currency' => "ZAR",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Togo",
                'flag' => "",
                'iso_code' => "TG",
                'phone_code' => "228",
                'currency' => "XOF",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Tunisie",
                'flag' => "",
                'iso_code' => "TN",
                'phone_code' => "216",
                'currency' => "TND",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Ouganda",
                'flag' => "",
                'iso_code' => "UG",
                'phone_code' => "256",
                'currency' => "UGX",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ],[
                'pays' => "Seychelles",
                'flag' => "",
                'iso_code' => "SC",
                'phone_code' => "248",
                'currency' => "SCR",
                'created_at' => date("Y-m-d H:i:s", strtotime(now())),
                'updated_at' => date("Y-m-d H:i:s", strtotime(now()))
            ]
        ];


        foreach($data as $item) {
            DB::table('pays')->insert($item);
        }

    }
}
