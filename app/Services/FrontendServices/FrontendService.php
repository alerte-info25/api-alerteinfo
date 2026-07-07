<?php

namespace App\Services\FrontendServices;

use App\Logs\CustomLogError;
use App\Models\News\NewsModel;
use Illuminate\Http\JsonResponse;
use App\Models\News\NewsRubriqueModel;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JsonResponseServices\JsonResponseService;

class FrontendService {

    public function __construct(
        private readonly NewsModel $newsModel,
        private readonly JsonResponseService $jsonResponseService,
        private readonly CustomLogError $customLogError,
        private readonly NewsRubriqueModel $rubriqueModel,
    ) {
    }

    /**
     * @return array {top_section_news: array, societe_section_news: array, politique_section_news: array, economie_section_news: array, international_section_news: array}
     */
    public function srv_getNews(): array
    {
        try {
            // Récupérer les paramètres de pagination

            $topSectionNews = $this->getTopSectionNews();
            $societeSectionNews = $this->getSocieteSectionNews();
            $politiqueSectionNews = $this->getPolitiqueSectionNews();
            $economieSectionNews = $this->getEconomieSectionNews();
            $internationalSectionNews = $this->srv_getInternationalSectionNews();
            $environmentSectionNews = $this->getEnvironmentSectionNews();

            return
                [
                    'top_section_news' => $topSectionNews,
                    'societe_section_news' => $societeSectionNews,
                    'politique_section_news' => $politiqueSectionNews,
                    'economie_section_news' => $economieSectionNews,
                    'international_section_news' => $internationalSectionNews,
                    'environment_section_news' => $environmentSectionNews,
                ];

        } catch (\Throwable $th) {
            $this->customLogError->logError(
                "Une erreur est survenue lors de la récupération des news",
                $th
            );
            return [];

        }
    }


    private function getTopSectionNews()
    {
        $news = $this->newsModel
        ->where('published', 'Publié') // seulement les articles publiés
        ->with(['rubrique', 'rubriqueCategory'])
        ->latest() // équivalent à orderByDesc('created_at')
        ->limit(6)
        ->get();

        return $news;
    }
    private function getTopPolitiqueSectionNews()
    {
        $news = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->whereHas('rubrique', function ($query) {
            $query->where('rubrique_name', 'POLITIQUE');
            // Ou mieux, si le nom est dans une autre table :
            // $query->whereHas('rubrique', fn($q) => $q->where('name', 'POLITIQUE'));
        })
        ->latest() // équivalent à orderByDesc('created_at')
        ->limit(6)
        ->get();

        return $news;
    }

    private function getSocieteSectionNews()
    {
        $news = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->whereHas('rubrique', function ($query) {
            $query->where('rubrique_name', 'SOCIÉTÉ');
        })
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();
        return $news;
    }

    private function getPolitiqueSectionNews()
    {
        $news = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('published', 'Publié') // seulement les articles publiés
        ->whereHas('rubrique', function ($query) {
            $query->where('rubrique_name', 'POLITIQUE');
        })
        ->orderByDesc('created_at')
        ->limit(4)
        ->get();
        return $news;
    }

    private function getEconomieSectionNews()
    {
        $news = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('published', 'Publié') // seulement les articles publiés
        ->whereHas('rubrique', function ($query) {
            $query->where('rubrique_name', 'ÉCONOMIE');
        })
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();
        return $news;
    }

    public function srv_getInternationalSectionNews()
    {
        $news = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('published', 'Publié') // seulement les articles publiés
        ->whereHas('rubrique', function ($query) {
            $query->where('rubrique_name', 'INTERNATIONAL');
        })
        ->orderByDesc('created_at')
        ->limit(1)
        ->get();
        return $news;
    }

    // environment
    private function getEnvironmentSectionNews()
    {
        $news = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('published', 'Publié') // seulement les articles publiés
        ->whereHas('rubrique', function ($query) {
            $query->where('rubrique_name', 'ENVIRONNEMENT');
        })
        ->orderByDesc('created_at')
        ->limit(3)
        ->get();
        return $news;
    }

    public function srv_getFlashInfoSectionNews()
    {
        $news = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('published', 'Publié') // seulement les articles publiés
        ->select('news_title','rubrique_code_unique','news_slug')
        ->orderByDesc('created_at')
        ->limit(7)
        ->get();
        return $news;
    }


    public function srv_getNewsDetails($slug)
    {
        $this->updateNewsViews($slug);


        $newsDetails = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('news_slug', $slug)
        ->first();

        // get similar news
        $similarNews = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('rubrique_code_unique', $newsDetails->rubrique_code_unique)
        ->where('news_slug', '!=', $slug)
        ->where('published', 'Publié') // seulement les articles publiés
        ->latest('created_at')    // les plus récents en premier
        ->limit(5)
        ->get();
        return [
            'newsDetails' => $newsDetails,
            'similarNews' => $similarNews
        ];
    }

    public function srv_getRubriqueNewsData($rubriqueSlug)
    {
        //\Log::info("Slug reçu : [$rubriqueSlug]");

        $rubriqueNews = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('published', "Publié") // ou 1 si boolean stocké en entier
        ->whereHas('rubrique', function ($query) use ($rubriqueSlug) {
            $query->where('slug', $rubriqueSlug); // suppose slug propre
        })
        ->latest('created_at') // équivalent à orderByDesc('created_at')
        ->paginate(6);

        // other rubrique news
        $otherRubriqueNews = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('published', "Publié") // ou 1 si boolean stocké en entier
        ->whereHas('rubrique', function ($query) use ($rubriqueSlug) {
            $query->where('slug', '!=', $rubriqueSlug); // suppose slug propre
        })
        ->latest('created_at') // équivalent à orderByDesc('created_at')
        ->limit(4)
        ->get();

        return [
            'rubriqueNews' => $rubriqueNews,
            'otherRubriqueNews' => $otherRubriqueNews
        ];
    }

    private function updateNewsViews($slug)
    {
        $news = $this->newsModel
        ->with(['rubrique', 'rubriqueCategory'])
        ->where('news_slug', $slug)
        ->first();
        $news->news_views = $news->news_views + 1;
        $news->save();
        
    }

    public function srv_getRubriqueNews()
    {
        // Explicitly fetch rubrics and count news for each to ensure accuracy
        $rubriques = $this->rubriqueModel->all();
        $data = [];
        
        foreach($rubriques as $rubrique) {
            $count = $this->newsModel
                        ->where('rubrique_code_unique', $rubrique->rubrique_code_unique)
                        ->where('published', 'Publié')
                        ->count();
            
            $rubriqueData = $rubrique->toArray();
            $rubriqueData['news_count'] = $count;
            $data[] = $rubriqueData;
        }
        
        return $data;
    }

    public function srv_getArchivesLinks()
    {
        // Group by year and month, count published news
        $archives = $this->newsModel
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->where('published', 'Publié')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Format for display (e.g., "Janvier 2024")
        $formattedArchives = $archives->map(function ($item) {
            $dateObj = \DateTime::createFromFormat('!m', $item->month);
            $monthName = $dateObj->format('F'); // English month name
            
            // French translation map
            $monthsFr = [
                'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars',
                'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
                'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre',
                'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
            ];
            
            $item->month_name = $monthsFr[$monthName] ?? $monthName;
            $item->month_padded = str_pad($item->month, 2, '0', STR_PAD_LEFT);
            return $item;
        });

        return $formattedArchives;
    }

    public function srv_getArchivesNews($year, $month)
    {
        $news = $this->newsModel
            ->with(['rubrique', 'rubriqueCategory'])
            ->where('published', 'Publié')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->latest('created_at')
            ->paginate(6); // 6 items per page

        return $news;
    }

}

