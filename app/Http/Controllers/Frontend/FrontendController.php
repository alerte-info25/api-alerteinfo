<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\FrontendServices\FrontendService;

class FrontendController extends Controller
{
    public function __construct(
        private readonly FrontendService $frontendService,
    ) {
    }

    public function index()
    {
        $dataNews = $this->frontendService->srv_getNews();
        $dataRubriqueNews = $this->frontendService->srv_getRubriqueNews();
        $flashInfoSectionNews = $this->frontendService->srv_getFlashInfoSectionNews();
        $archivesLinks = $this->frontendService->srv_getArchivesLinks();
        $internationalSectionNews = $this->frontendService->srv_getInternationalSectionNews();
        //dd($archivesLinks);

        return view('pages.index', compact(
            'dataNews',
            'dataRubriqueNews',
            'flashInfoSectionNews',
            'internationalSectionNews',
            'archivesLinks'
        ));
    }

    public function newsDetails($slug)
    {
        $dataNews = $this->frontendService->srv_getNewsDetails($slug);
        $dataRubriqueNews = $this->frontendService->srv_getRubriqueNews();
        $flashInfoSectionNews = $this->frontendService->srv_getFlashInfoSectionNews();
        $archivesLinks = $this->frontendService->srv_getArchivesLinks();
        //dd($dataRubriqueNews);

        return view('pages.news-details', compact(
            'dataNews',
            'dataRubriqueNews',
            'flashInfoSectionNews',
            'archivesLinks'

        ));
    }

    public function rubriqueNews($rubrique,$rubriqueSlug)
    {
        $dataNews = $this->frontendService->srv_getRubriqueNewsData($rubriqueSlug);
        $dataRubriqueNews = $this->frontendService->srv_getRubriqueNews();
        $flashInfoSectionNews = $this->frontendService->srv_getFlashInfoSectionNews();
        $archivesLinks = $this->frontendService->srv_getArchivesLinks();
        //dd($dataNews);

        return view('pages.rubrique-news', compact(
            'dataNews',
            'dataRubriqueNews',
            'flashInfoSectionNews',
            'archivesLinks'
        ));
    }

    public function archives($year, $month)
    {
        $dataNews = $this->frontendService->srv_getArchivesNews($year, $month);
        $dataRubriqueNews = $this->frontendService->srv_getRubriqueNews();
        $flashInfoSectionNews = $this->frontendService->srv_getFlashInfoSectionNews();
        $archivesLinks = $this->frontendService->srv_getArchivesLinks();
        $internationalSectionNews = $this->frontendService->srv_getInternationalSectionNews();

        return view('pages.archives', compact(
            'dataNews',
            'dataRubriqueNews',
            'flashInfoSectionNews',
            'archivesLinks',
            'internationalSectionNews',
            'year',
            'month'
        ));
    }
}
