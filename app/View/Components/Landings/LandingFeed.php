<?php

namespace App\View\Components\Landings;

use Closure;
use Carbon\Carbon;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

use App\Services\FrontendServices\FrontendService;

class LandingFeed extends Component
{
    public $dataNews;
    public $topSectionNews;
    public $topSectionFirstNews;
    public $topSectionSecondNews;
    public $topSectionThirdNews;
    public $topSectionFourthNews;
    public $topSectionFifthNews;
    public $topSectionSixthNews;

    public $societeSectionNews;
    public $societeSectionFirstNews;
    public $societeSectionSecondNews;
    public $societeSectionThirdNews;
    public $societeSectionFourthNews;
    public $societeSectionFifthNews;
    public $politiqueSectionNews;
    public $economieSectionNews;
    public $internationalSectionNews;
    public $environmentSectionNews;
    
    public $archivesLinks;

    public $dataRubriqueNews;

    /**
     * Create a new component instance.
     */
    public function __construct($dataNews, $dataRubriqueNews, $archivesLinks, $internationalSectionNews)
    {
        $this->dataNews = $dataNews;
        $this->dataRubriqueNews = $dataRubriqueNews;
        $this->archivesLinks = $archivesLinks;
        $this->internationalSectionNews = $internationalSectionNews;

        $topSection = data_get($dataNews, 'top_section_news');
        $this->topSectionNews = $topSection instanceof Collection ? $topSection->toArray() : (is_array($topSection) ? $topSection : []);
        
        $this->topSectionFirstNews = $this->topSectionNews[0] ?? null;
        $this->topSectionSecondNews = $this->topSectionNews[1] ?? null;
        $this->topSectionThirdNews = $this->topSectionNews[2] ?? null;
        $this->topSectionFourthNews = $this->topSectionNews[3] ?? null;
        $this->topSectionFifthNews = $this->topSectionNews[4] ?? null;
        $this->topSectionSixthNews = $this->topSectionNews[5] ?? null;

        $societeSection = data_get($dataNews, 'societe_section_news');
        $this->societeSectionNews = $societeSection instanceof Collection ? $societeSection->toArray() : (is_array($societeSection) ? $societeSection : []);

        $this->societeSectionFirstNews = $this->societeSectionNews[0] ?? null;
        $this->societeSectionSecondNews = $this->societeSectionNews[1] ?? null;
        $this->societeSectionThirdNews = $this->societeSectionNews[2] ?? null;
        $this->societeSectionFourthNews = $this->societeSectionNews[3] ?? null;
        $this->societeSectionFifthNews = $this->societeSectionNews[4] ?? null;


        $politiqueSection = data_get($dataNews, 'politique_section_news');
        $this->politiqueSectionNews = $politiqueSection instanceof Collection ? $politiqueSection->toArray() : (is_array($politiqueSection) ? $politiqueSection : []);

        $economieSection = data_get($dataNews, 'economie_section_news');
        $this->economieSectionNews = $economieSection instanceof Collection ? $economieSection->toArray() : (is_array($economieSection) ? $economieSection : []);

        $environmentSection = data_get($dataNews, 'environment_section_news');
        $this->environmentSectionNews = $environmentSection instanceof Collection ? $environmentSection->toArray() : (is_array($environmentSection) ? $environmentSection : []);
        
    }

    public function formatDate($date)
    {
        return $date ? Carbon::parse($date)
        ->locale('fr')
        ->diffForHumans() : null;
    }
    public function sliceNewsLead($newsLead)
    {
        return substr($newsLead, 0, 243) . '...';
    }
    public function formatAbsoluteDate($date)
    {
        return $date ? Carbon::parse($date)
        ->locale('fr')
        ->translatedFormat('d M Y') : null;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $formatDate = function ($date) {
            return $this->formatDate($date);
        };
        $formatAbsoluteDate = function ($date) {
            return $this->formatAbsoluteDate($date);
        };
        $sliceNewsLead = function ($newsLead) {
            return $this->sliceNewsLead($newsLead);
        };
        //dd($this->internationalSectionNews);
        return view('components.landings.landing-feed', compact('formatDate', 'formatAbsoluteDate', 'sliceNewsLead'));
    }
}
