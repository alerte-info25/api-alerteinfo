<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FooterSettingRessource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'description_1'          => $this->description_1,
            'description_2'          => $this->description_2,
            'description_3'          => $this->description_3,
            'phones'                 => $this->phones ?? [],
            'email_direction'        => $this->email_direction,
            'email_redaction'        => $this->email_redaction,
            'address_abidjan_city'   => $this->address_abidjan_city,
            'address_abidjan_detail' => $this->address_abidjan_detail,
            'address_ouaga_city'     => $this->address_ouaga_city,
            'address_ouaga_detail'   => $this->address_ouaga_detail,
            'facebook_url'           => $this->facebook_url,
            'youtube_url'            => $this->youtube_url,
            'updated_at'             => $this->updated_at,
        ];
    }
}
