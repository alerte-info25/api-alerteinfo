<?php

namespace App\Services\NewsApiService;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Quoideneufs\ArticlesModels;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\PartnersNews\ApiPartnerNewsModel;
use App\Models\PartnersNews\ApiPartnerNewsLogModel;

class NewsApiService
{
    // ... autres méthodes ...

    public function getPublishedNews(int $page = 1, int $limit = 20, ?string $publishedAfter = null): LengthAwarePaginator
    {
        $limit = min($limit, 100);

        // Début et fin du mois en cours
        $startOfMonth = now()->subMonth()->startOfMonth()->addDays(14);   // 2025-04-01 00:00:00
        $endOfMonth   = now()->endOfMonth();     // 2025-04-30 23:59:59

        $query = ArticlesModels::query()
        ->where('status', 1)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

        Log::info("Query: [{$query->toSql()}]");

        if ($publishedAfter) {
            try {
                $date = Carbon::parse($publishedAfter);
                $query->where('created_at', '>=', $date);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException("Format de date invalide pour 'published_after'.");
            }
        }

        $cacheKey = "api_news_page_{$page}_limit_{$limit}" . ($publishedAfter ? "_from_" . md5($publishedAfter) : '');

        Log::info("Cache Key: [$cacheKey ]");

        return \Cache::remember($cacheKey, now()->addMinutes(10), function () use ($query, $limit) {
            return $query->orderBy('created_at', 'desc')->paginate($limit);
        });
    }


    /**
     * Enregistre l'accès à l'API dans la base de données
     */
    public function logPartnerAccess(
        $partner,
        string $endpoint,
        string $method = 'GET',
        int $statusCode = 200,
        array $query = [],
        ?string $errorMessage = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        try {
            ApiPartnerNewsLogModel::create([
                'partner_code_unique' => $partner?->partner_code_unique,
                'endpoint' => $endpoint,
                'http_method' => $method,
                'response_status' => $statusCode,
                'ip_address' => $ip,
                'user_agent' => Str::limit($userAgent, 500),
                'query_params' => $query,
                'error_message' => $errorMessage,
                'requested_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Évite de planter l'API si le log échoue
            \Log::warning('Échec enregistrement ApiLog: ' . $e->getMessage());
        }
    }


    // Optionnel : méthode pour nettoyer les logs anciens
    public function cleanupOldLogs($days = 90)
    {
        return ApiPartnerNewsLogModel::where('requested_at', '<', now()->subDays($days))->delete();
    }



    public function getPartnerToken()
    {
        return ApiPartnerNewsModel::where('is_active', true)->get();
    }

    public function createPartnerToken(Request $request)
    {
        return ApiPartnerNewsModel::create([
            'name' => $request->name,
            'email' => $request->email,
            'api_token' => $this->generateAlphanumericToken(),
            'rate_limit' => $request->rate_limit,
            'is_active' => $request->is_active,
            'last_used_at' => now(),
            'slug' => Str::uuid(),
        ]);
    }

    private function generateAlphanumericToken(): string
    {
        do {
            $token = strtoupper(Str::random(60));
        } while (! preg_match('/^[A-Z0-9]{60}$/', $token));

        return $token;
    }

    public function updatePartnerToken(Request $request, $slug)
    {
        $partner = ApiPartnerNewsModel::where('slug', $slug)->first();
        $partner->update([
            'name' => $request->name,
            'email' => $request->email,
            'rate_limit' => $request->rate_limit,
            'is_active' => $request->is_active,
        ]);
        return $partner;
    }
}
