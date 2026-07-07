<?php

namespace App\View\Components\FlashInfos;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Collection;

class flashInfos extends Component
{
    public $flashInfoSectionNews;
    /**
     * Create a new component instance.
     */
    public function __construct($flashInfoSectionNews)
    {
        $this->flashInfoSectionNews = $flashInfoSectionNews->toArray();;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        //dd($this->flashInfoSectionNews);
        return view('components.flash-infos.flash-infos');
    }
}
