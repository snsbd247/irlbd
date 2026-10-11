<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Product;
use App\Services\SettingsService;
use App\Services\SitemapService;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SiteController extends Controller
{
    public function settings(SettingsService $settings): JsonResponse
    {
        return response()->json($settings->publicPayload());
    }

    public function navigation(): JsonResponse
    {
        $items = MenuItem::active()
            ->whereNull('parent_id')
            ->with(['page', 'children' => fn ($q) => $q->where('is_active', true)->with('page')])
            ->ordered()
            ->get()
            ->filter(fn (MenuItem $item) => $this->isVisible($item))
            ->each(fn (MenuItem $item) => $item->setRelation(
                'children',
                $item->children->filter(fn ($child) => $this->isVisible($child))->values()
            ));

        $out = [];
        foreach (MenuItem::LOCATIONS as $location) {
            $out[$location] = MenuItemResource::collection($items->where('location', $location)->values());
        }

        return response()->json($out);
    }

    /** Menu items pointing at a page are hidden when that page is missing or unpublished. */
    protected function isVisible(MenuItem $item): bool
    {
        if ($item->link_type !== 'page') {
            return true;
        }

        return $item->page && $item->page->status === 'published';
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => 'required|string|min:2|max:100']);
        $term = '%'.addcslashes($data['q'], '%_\\').'%';

        $products = Product::visible()
            ->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('short_description', 'like', $term)->orWhere('sku', 'like', $term))
            ->ordered()->limit(10)->get()
            ->map(fn ($p) => ['type' => 'product', 'title' => $p->name, 'excerpt' => $p->short_description, 'url' => '/products/'.$p->slug, 'image_url' => MediaUrl::url($p->image)]);

        $news = NewsPost::published()
            ->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('excerpt', 'like', $term))
            ->latest('published_at')->limit(10)->get()
            ->map(fn ($n) => ['type' => 'news', 'title' => $n->title, 'excerpt' => $n->excerpt, 'url' => '/news/'.$n->slug, 'image_url' => MediaUrl::url($n->featured_image)]);

        $pages = Page::published()
            ->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('hero_subtitle', 'like', $term)->orWhere('meta_description', 'like', $term))
            ->limit(10)->get()
            ->map(fn ($p) => ['type' => 'page', 'title' => $p->title, 'excerpt' => $p->hero_subtitle ?: $p->meta_description, 'url' => $p->publicPath(), 'image_url' => null]);

        return response()->json(['query' => $data['q'], 'results' => $pages->concat($products)->concat($news)->values()]);
    }

    public function sitemap(SitemapService $sitemap): Response
    {
        return response($sitemap->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
