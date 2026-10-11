<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\SectionHydrator;

class PageController extends Controller
{
    public function __construct(protected SectionHydrator $hydrator) {}

    public function home(): PageResource
    {
        $page = Page::published()->where('is_home', true)->firstOrFail();

        return $this->present($page);
    }

    public function show(string $slug): PageResource
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return $this->present($page);
    }

    protected function present(Page $page): PageResource
    {
        $page->load(['sections' => fn ($q) => $q->where('is_active', true)
            ->with(['items' => fn ($i) => $i->where('is_active', true)])]);

        $this->hydrator->hydrate($page->sections);

        return new PageResource($page);
    }
}
