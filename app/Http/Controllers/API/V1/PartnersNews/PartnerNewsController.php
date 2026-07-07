<?php

namespace App\Http\Controllers\API\V1\PartnersNews;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\NewsApiService\NewsApiService;

class PartnerNewsController extends Controller
{
    public function __construct(private readonly NewsApiService $newsService){}


    public function ctrl_getPartnerNews(Request $request)
    {
        // Récupéré par le middleware ApiPartnerAuth
        $partner = $request->attributes->get('api_partner');

        $limit = (int) $request->get('limit', 5);
        $page = (int) $request->get('page', 1);
        $publishedAfter = $request->get('published_after');

        try {
            $news = $this->newsService->getPublishedNews($page, $limit, $publishedAfter);

            $this->newsService->logPartnerAccess(
                partner: $partner,
                endpoint: '/v1/partner/news',
                method: 'GET',
                statusCode: 200,
                query: $request->query(),
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            
            return response()->json([
                'success' => true,
                'partner' => [
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'rate_limit' => $partner->rate_limit,
                    'is_active' => $partner->is_active,
                ],
                'data' => $news->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->titre,
                        'slug' => $item->slug,
                        'content' => $item->contenus,
                        'author' => $item->author ?? "Quoideneuf.info",
                        'published_at' => $item->created_at?->toISOString(),
                        'image_url' => $item->media_url ? url('storage/' . $item->media_url) : null,
                        'rubique' => $item->rubrique->rubrique,
                        'genre' => $item->genre->genre,
                        'country' => $item->country->pays,
                        'source_url' => "Quoideneuf.info",
                    ];
                })->values(),
                'pagination' => [
                    'current_page' => $news->currentPage(),
                    'per_page' => $news->perPage(),
                    'total' => $news->total(),
                    'last_page' => $news->lastPage(),
                    'next_page_url' => $news->nextPageUrl(),
                    'prev_page_url' => $news->previousPageUrl(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->newsService->logPartnerAccess(
                partner: $partner,
                endpoint: '/v1/partner/news',
                method: 'GET',
                statusCode: 400,
                query: $request->query(),
                errorMessage: $e->getMessage(),
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }catch (\Exception $e) {
            $this->newsService->logPartnerAccess(
                partner: $partner,
                endpoint: '/v1/news',
                method: 'GET',
                statusCode: 500,
                query: $request->query(),
                errorMessage: $e->getMessage(),
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur.',
            ], 500);
        }
    }
}
