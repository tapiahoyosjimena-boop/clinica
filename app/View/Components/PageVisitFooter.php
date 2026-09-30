<?php

namespace App\View\Components;

use App\Models\PageVisit;
use App\Support\PageVisitKey;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PageVisitFooter extends Component
{
    public ?string $pageKey;

    public int $hits;

    public function __construct(
        public string $variant = 'portal',
    ) {
        $this->pageKey = PageVisitKey::resolve(request());
        $this->hits = $this->pageKey !== null
            ? (int) PageVisit::query()->where('route_key', $this->pageKey)->value('hits')
            : 0;
    }

    public function render(): View
    {
        return view('components.page-visit-footer');
    }
}
