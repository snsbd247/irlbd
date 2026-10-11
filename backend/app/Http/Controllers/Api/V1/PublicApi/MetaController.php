<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\JobPost;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Services\SettingsService;
use App\Support\Html;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Title / description / image for any public URL path. Used by the small share.php
 * script on the website host to give social-media link previews (WhatsApp, Facebook,
 * LinkedIn...) real per-page metadata, because those crawlers do not run JavaScript.
 */
class MetaController extends Controller
{
    public function __invoke(Request $request, SettingsService $settings): JsonResponse
    {
        $path = '/'.trim((string) $request->query('path', '/'), '/');
        $path = strtok($path, '?') ?: '/';
        abort_unless(preg_match('#^/[A-Za-z0-9/_-]*$#', $path) && strlen($path) <= 300, 422, 'Invalid path.');

        $s = $settings->publicPayload();
        $defaults = [
            'title' => $s['seo_title'] ?: $s['site_name'],
            'description' => $s['seo_description'],
            'image' => $s['seo_og_image_url'] ?? null,
            'type' => 'website',
        ];
        $siteName = $s['site_name'] ?: 'IR Limited';
        $suffix = ' '.($s['seo_title_separator'] ?: '|').' '.$siteName;

        $segments = array_values(array_filter(explode('/', $path)));
        $meta = match (true) {
            $segments === [] => $this->page(Page::published()->where('is_home', true)->first(), $defaults, ''),
            count($segments) === 2 && $segments[0] === 'products' => $this->product($segments[1]),
            count($segments) === 2 && $segments[0] === 'news' => $this->news($segments[1]),
            count($segments) === 2 && $segments[0] === 'services' => $this->simple(Service::active()->where('slug', $segments[1])->first(), 'title', 'short_description', 'image'),
            count($segments) === 2 && $segments[0] === 'careers' => $this->simple(JobPost::published()->where('slug', $segments[1])->first(), 'title', 'summary', null, 'meta_title', 'meta_description'),
            count($segments) === 2 && $segments[0] === 'gallery' => $this->simple(GalleryAlbum::active()->where('slug', $segments[1])->first(), 'title', 'description', 'cover_image'),
            count($segments) === 1 => $this->page(Page::published()->where('slug', $segments[0])->first(), $defaults, $suffix),
            default => null,
        };

        $meta = array_merge($defaults, array_filter($meta ?? [], fn ($v) => filled($v)));
        if (! empty($meta['_suffix']) && ! str_contains($meta['title'], $siteName)) {
            $meta['title'] .= $suffix;
        }
        unset($meta['_suffix']);

        $meta['site_name'] = $siteName;
        $meta['url'] = rtrim(config('cms.frontend_url'), '/').($path === '/' ? '/' : $path);
        if (app()->getLocale() !== config('cms.default_locale')) {
            $meta['url'] .= '?lang='.app()->getLocale();
        }
        $meta['description'] = Html::plain($meta['description'] ?? '', 300);

        return response()->json($meta)->setPublic()->setMaxAge(600);
    }

    protected function page(?Page $page, array $defaults, string $suffix): ?array
    {
        if (! $page) {
            return null;
        }

        return [
            'title' => $page->meta_title ?: ($page->is_home ? $defaults['title'] : ($page->hero_title ?: $page->title)),
            'description' => $page->meta_description ?: $page->hero_subtitle,
            'image' => MediaUrl::url($page->og_image ?: $page->hero_image),
            '_suffix' => $suffix !== '',
        ];
    }

    protected function product(string $slug): ?array
    {
        $p = Product::visible()->where('slug', $slug)->first();

        return $p ? [
            'title' => $p->meta_title ?: $p->name, 'description' => $p->meta_description ?: $p->short_description,
            'image' => MediaUrl::url($p->image), 'type' => 'product', '_suffix' => true,
        ] : null;
    }

    protected function news(string $slug): ?array
    {
        $n = NewsPost::published()->where('slug', $slug)->first();

        return $n ? [
            'title' => $n->meta_title ?: $n->title, 'description' => $n->meta_description ?: ($n->excerpt ?: $n->content),
            'image' => MediaUrl::url($n->featured_image), 'type' => 'article', '_suffix' => true,
        ] : null;
    }

    protected function simple($model, string $title, ?string $description, ?string $image, ?string $metaTitle = null, ?string $metaDescription = null): ?array
    {
        if (! $model) {
            return null;
        }

        return [
            'title' => ($metaTitle ? $model->{$metaTitle} : null) ?: $model->{$title},
            'description' => ($metaDescription ? $model->{$metaDescription} : null) ?: ($description ? $model->{$description} : null),
            'image' => $image ? MediaUrl::url($model->{$image}) : null,
            '_suffix' => true,
        ];
    }
}
