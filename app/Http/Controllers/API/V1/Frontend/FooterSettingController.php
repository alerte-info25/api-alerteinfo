<?php

namespace App\Http\Controllers\API\V1\Frontend;

use App\Http\Controllers\Controller;
use App\Services\FrontendQuoideneufServices\FooterSettingService;
use App\Http\Resources\FooterSettingRessource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FooterSettingController extends Controller
{
    public function __construct(
        private readonly FooterSettingService $footerSettingsService
    ) {}

    /**
     * GET /api/footer-settings
     * Accessible publiquement par le frontend.
     */
    public function show(): FooterSettingRessource
    {
        return new FooterSettingRessource(
            $this->footerSettingsService->get()
        );
    }

    /**
     * PUT /api/admin/footer-settings
     * Réservé au backoffice (middleware auth:sanctum dans les routes).
     */
    public function update(Request $request): FooterSettingRessource|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'description_1'          => 'nullable|string|max:1000',
            'description_2'          => 'nullable|string|max:1000',
            'description_3'          => 'nullable|string|max:1000',
            'phones'                 => 'nullable|array',
            'phones.*.label'         => 'required_with:phones|string|max:100',
            'phones.*.number'        => 'required_with:phones|string|max:30',
            'email_direction'        => 'nullable|email|max:255',
            'email_redaction'        => 'nullable|email|max:255',
            'address_abidjan_city'   => 'nullable|string|max:255',
            'address_abidjan_detail' => 'nullable|string|max:500',
            'address_ouaga_city'     => 'nullable|string|max:255',
            'address_ouaga_detail'   => 'nullable|string|max:500',
            'facebook_url'           => 'nullable|url|max:500',
            'youtube_url'            => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors'  => $validator->errors(),
            ], 422);
        }
        return new FooterSettingRessource(
            $this->footerSettingsService->update($validator->validated())
        );
    }
}
