<?php

namespace App\View\Components\RubriqueNews;

use Closure;
use Carbon\Carbon;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class RubriqueNews extends Component
{
    public $dataNews;
    public $rubriqueNews;
    public $archivesLinks;

    /**
     * Create a new component instance.
     */
    public function __construct($dataNews, $rubriqueNews)
    {
        $this->dataNews = $dataNews;
        $this->rubriqueNews = $rubriqueNews;

        // Resolve service manually since we can't easily change constructor signature if called from Blade
        $this->archivesLinks = app(\App\Services\FrontendServices\FrontendService::class)->srv_getArchivesLinks();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {

        return view('components.rubrique-news.rubrique-news');
    }
}
