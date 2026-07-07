<?php

namespace App\View\Components\NewsDetails;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Carbon\Carbon;

class NewsDetails extends Component
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

    public function formatDate($date)
    {
        return $date ? Carbon::parse($date)
        ->locale('fr')
        ->diffForHumans() : null;
    }


    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        //dd($this->dataNews);
        return view('components.news-details.news-details');
    }
}
