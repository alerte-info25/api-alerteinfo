<?php

namespace App\Http\Controllers\API\V1\SEO;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OgMetaController extends Controller
{
    public function handle($slug, Request $request)
    {
        $userAgent = strtolower($request->header('User-Agent', ''));

        $bots = [
            'whatsapp', 'facebookexternalhit', 'twitterbot',
            'telegrambot', 'linkedinbot', 'slackbot', 'discordbot',
            'googlebot', 'bingbot'
        ];

        $isBot = false;
        foreach ($bots as $bot) {
            if (str_contains($userAgent, $bot)) {
                $isBot = true;
                break;
            }
        }

        $article = DB::table('articles_models')
            ->where('slug', $slug)
            ->select('titre', 'lead', 'media_url', 'slug')
            ->first();

        if (!$article) {
            return redirect('https://www.quoideneuf.info/');
        }

        $title       = htmlspecialchars($article->titre ?? 'Quoideneuf');
        $description = htmlspecialchars($article->lead  ?? 'Actualités Côte d\'Ivoire');
        $image       = $article->media_url ?? 'https://quoideneuf.info/assets/logo-qdn_1.png';
        $url         = 'https://www.quoideneuf.info/article/' . $slug;

        if ($isBot) {
            $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <meta name="description" content="{$description}" />

    <!-- Open Graph -->
    <meta property="og:type"        content="article" />
    <meta property="og:site_name"   content="Quoideneuf" />
    <meta property="og:locale"      content="fr_FR" />
    <meta property="og:title"       content="{$title}" />
    <meta property="og:description" content="{$description}" />
    <meta property="og:image"       content="{$image}" />
    <meta property="og:image:width"  content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:url"         content="{$url}" />

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image" />
    <meta name="twitter:title"       content="{$title}" />
    <meta name="twitter:description" content="{$description}" />
    <meta name="twitter:image"       content="{$image}" />
</head>
<body>
    <script>window.location.href = "{$url}";</script>
</body>
</html>
HTML;
            return response($html, 200)->header('Content-Type', 'text/html');
        }

        // Utilisateur normal → redirection vers Angular
        return redirect($url);
    }
}
