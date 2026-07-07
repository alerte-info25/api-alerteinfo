<?php
namespace App\Http\Controllers\API\RH;

use App\Http\Controllers\Controller;
use App\Models\Quoideneufs\ArticlesModels;
use App\Models\Quoideneufs\MediaModels;
use App\Models\Redactions\DepecheModels;
use App\Models\Redactions\FlashesModels;
use App\Models\UsersAccounts\AdministrationModels;
use Illuminate\Http\Request;

class JournalisteStatsController extends Controller
{
    /**
     * Liste des genres considérés comme des articles standards
     */
    private $articleGenres = [
        'COMPTE RENDU',
        'FILET',
        'PORTRAIT',
        'ANALYSE',
        'ENQUETE',
        'TRIBUNE',
        'DÉPÊCHE FACTUELLE',
        'DEPECHE FACTUELLE',
        'EDITORIAL',
        'BILLET',
        'CHRONIQUE',
        'MAGAZINE'
    ];

    public function getStats(Request $request)
    {
        // 1. Validation des paramètres
        if (!$request->has(['annee', 'trimestre', 'rh_slug'])) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres requis : annee, trimestre, rh_slug'
            ], 400);
        }

        $annee = (int) $request->annee;
        $trimestre = (int) $request->trimestre;
        $rhSlug = $request->rh_slug;

        // 2. Validation des valeurs
        if ($trimestre < 1 || $trimestre > 4) {
            return response()->json([
                'success' => false,
                'message' => 'Le trimestre doit être entre 1 et 4'
            ], 400);
        }

        // 3. Récupération des dates
        $dates = $this->getTrimestreDates($annee, $trimestre);
        if (!$dates) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de déterminer les dates du trimestre'
            ], 400);
        }

        // 4. Recherche du journaliste
        $journaliste = AdministrationModels::where('rh_slug', $rhSlug)->first();

        if (!$journaliste) {
            return response()->json([
                'success' => false,
                'message' => 'Journaliste introuvable !'
            ], 404);
        }

        $authorName = $journaliste->first_name . ' ' . $journaliste->last_name;

        // 5. Récupération des productions
        $articles = ArticlesModels::with('genre')
            ->where('author', $authorName)
            ->whereBetween('created_at', [$dates['debut'], $dates['fin']])
            ->get();

        $depeches = DepecheModels::where('author', $authorName)
            ->whereBetween('created_at', [$dates['debut'], $dates['fin']])
            ->get();

        // $flashes = FlashesModels::where('author', $authorName)
        //     ->whereBetween('created_at', [$dates['debut'], $dates['fin']])
        //     ->get();

        $videos = MediaModels::where('author', $authorName)
            ->where('type_media', 'video')
            ->whereBetween('created_at', [$dates['debut'], $dates['fin']])
            ->get();
        // return response()->json([
        //     'success' => true,
        //     'videos' => $videos
        // ]);

        // 6. Initialisation des compteurs
        $totalArticles = 0;
        $totalInterviews = 0;
        $totalReportages = 0;
        $totalVideos = 0;
        // $totalFlashes = 0;

        $allLinks = [];

        // 6.1 Traitement des ARTICLES
        foreach ($articles as $article) {
            $genreName = $article->genre ? strtoupper(trim($article->genre->genre)) : null;

            // Déterminer le type de contenu et le compteur
            if (in_array($genreName, $this->articleGenres) || is_null($genreName)) {
                // Article standard
                $totalArticles++;
                $linkType = 'article';
            }
            elseif ($genreName === 'REPORTAGE') {
                $totalReportages++;
                $linkType = 'reportage';
            }
            elseif ($genreName === 'INTERVIEW') {
                $totalInterviews++;
                $linkType = 'interview';
            }
            elseif ($genreName === 'MICRO-TROTTOIR') {
                $totalVideos++;
                $linkType = 'video';
            }
            else {
                // Par défaut, considérer comme article
                $totalArticles++;
                $linkType = 'article';
            }

            $allLinks[] = [
                'type' => $linkType,
                'lien' => $this->getArticleUrl($article),
                'titre' => $article->titre,
                'date' => $article->created_at->format('Y-m-d H:i:s')
            ];
        }

        // 6.2 Traitement des DEPECHES (toujours considérées comme des articles)
        foreach ($depeches as $depeche) {
            $totalArticles++;
            $allLinks[] = [
                'type' => 'article',
                'lien' => $this->getDepecheUrl($depeche),
                'titre' => $depeche->titre,
                'date' => $depeche->created_at->format('Y-m-d H:i:s')
            ];
        }

        // foreach ($flashes as $flash) {
        //     $totalFlashes++;
        //     $allLinks[] = [
        //         'type' => 'flash',
        //         'lien' => $this->getFlashUrl($flash),
        //         'contenu' => substr($flash->contenus, 0, 200) . (strlen($flash->contenus) > 200 ? '...' : ''),
        //         'date' => $flash->created_at->format('Y-m-d H:i:s')
        //     ];
        // }

        // 6.3 Traitement des VIDEOS (MediaModels)
        foreach ($videos as $video) {
            $totalVideos++;
            $allLinks[] = [
                'type' => 'video',
                'lien' => $this->getVideoUrl($video),
                'description' => $video->description,
                'date' => $video->created_at->format('Y-m-d H:i:s')
            ];
        }

        // 7. Construction de la réponse
        return response()->json([
            'success' => true,
            'data' => [
                'nombre_articles' => $totalArticles,
                'nombre_interviews' => $totalInterviews,
                'nombre_reportages' => $totalReportages,
                'nombre_videos' => $totalVideos,
                // 'nombre_flashes' => $totalFlashes,
                'articles' => $allLinks
            ],
            'meta' => [
                'period' => [
                    'annee' => $annee,
                    'trimestre' => $trimestre,
                    'date_debut' => $dates['debut'],
                    'date_fin' => $dates['fin']
                ],
                'journaliste' => [
                    'nom' => $journaliste->first_name . ' ' . $journaliste->last_name,
                    'rh_slug' => $rhSlug
                ],
                'counters_detail' => [
                    'articles_standards' => $totalArticles - ($depeches->count()),
                    'depeches' => $depeches->count(),
                    // 'flashes' => $flashes->count(),
                    'interviews' => $totalInterviews,
                    'reportages' => $totalReportages,
                    'videos' => $totalVideos
                ]
            ]
        ]);
    }

    private function getTrimestreDates($annee, $trimestre)
    {
        if (!$annee || !$trimestre || $trimestre < 1 || $trimestre > 4) {
            return null;
        }

        $dates = [
            1 => ['debut' => '-01-01 00:00:00', 'fin' => '-03-31 23:59:59'],
            2 => ['debut' => '-04-01 00:00:00', 'fin' => '-06-30 23:59:59'],
            3 => ['debut' => '-07-01 00:00:00', 'fin' => '-09-30 23:59:59'],
            4 => ['debut' => '-10-01 00:00:00', 'fin' => '-12-31 23:59:59'],
        ];

        if (!isset($dates[$trimestre])) {
            return null;
        }

        return [
            'debut' => $annee . $dates[$trimestre]['debut'],
            'fin' => $annee . $dates[$trimestre]['fin']
        ];
    }

    private function getArticleUrl($article): string
    {
        return 'https://www.quoideneuf.info/article/' . $article->slug;
    }
    private function getDepecheUrl($depeche): string
    {
        return 'https://www.alerte-info.net/accueil/article/' . $depeche->slug;
    }

    private function getFlashUrl($flash): string
    {
        return 'https://admin-redaction.alerte-info.net/redaction.view-flash/' . $flash->slug;
    }

    private function getVideoUrl($video): string
    {
        // Si c'est un ID YouTube simple, construire l'URL complète
        if ($video->video_url && !preg_match('/^https?:\/\//', $video->video_url)) {
            return 'https://www.youtube.com/watch?v=' . $video->video_url;
        }
        return $video->video_url ?? '#';
    }

}
