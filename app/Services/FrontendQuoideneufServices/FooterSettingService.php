<?php

namespace App\Services\FrontendQuoideneufServices;

use App\Models\FooterSetting;

class FooterSettingService
{
    /**
     * Récupère la configuration du footer.
     */
    public function get(): FooterSetting
    {
        return FooterSetting::getInstance();
    }

    /**
     * Met à jour la configuration du footer.
     *
     * @param  array<string, mixed>  $data  Données validées par le Controller
     */
    public function update(array $data): FooterSetting
    {
        $settings = FooterSetting::getInstance();
        $settings->update($data);

        return $settings->fresh();
    }
}
