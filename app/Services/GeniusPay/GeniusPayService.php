<?php

namespace App\Services\GeniusPay;

use Illuminate\Support\Facades\Http;

class GeniusPayService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = GeniusMarchand::getBaseUrl();
    }

    private function headers(): array
    {
        return [
            'X-API-Key' => GeniusMarchand::getApiKey(),
            'X-API-Secret' => GeniusMarchand::getApiSecret(),
            'Content-Type' => 'application/json'
        ];
    }

    public function createPayment(array $data)
    {
        return Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/payments", $data)
            ->json();
    }

    public function getPayment(string $reference)
    {
        return Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/payments/{$reference}")
            ->json();
    }
}
