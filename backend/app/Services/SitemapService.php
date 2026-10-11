<?php

namespace App\Services;

use App\Models\JobPost;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;

class SitemapService
{
    public function xml(): string
    {
        $base = rtrim(config('cms.frontend_url'), '/');
        $urls = [];

        foreach (Page::published()->get() as $page) {
            $urls[] = [$base.$page->publicPath(), $page->updated_at, $page->is_home ? '1.0' : '0.8'];
        }
        foreach (ProductCategory::active()->ordered()->get() as $category) {
            $urls[] = [$base.'/products?category='.$category->slug, $category->updated_at, '0.7'];
        }
        foreach (Product::visible()->get() as $product) {
            $urls[] = [$base.'/products/'.$product->slug, $product->updated_at, '0.7'];
        }
        foreach (Service::active()->get() as $service) {
            $urls[] = [$base.'/services/'.$service->slug, $service->updated_at, '0.6'];
        }
        foreach (JobPost::open()->get() as $job) {
            $urls[] = [$base.'/careers/'.$job->slug, $job->updated_at, '0.5'];
        }
        foreach (NewsPost::published()->latest('published_at')->get() as $post) {
            $urls[] = [$base.'/news/'.$post->slug, $post->updated_at, '0.6'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        $e = fn (string $s) => htmlspecialchars($s, ENT_XML1);
        foreach ($urls as [$loc, $updated, $priority]) {
            // Every page exists in English and Bengali (?lang=bn); list both, each with its alternates.
            $bn = $loc.(str_contains($loc, '?') ? '&' : '?').'lang=bn';
            $alternates = '<xhtml:link rel="alternate" hreflang="en" href="'.$e($loc).'"/>'
                .'<xhtml:link rel="alternate" hreflang="bn" href="'.$e($bn).'"/>'
                .'<xhtml:link rel="alternate" hreflang="x-default" href="'.$e($loc).'"/>';
            foreach ([$loc, $bn] as $i => $url) {
                $xml .= '  <url><loc>'.$e($url).'</loc>'.$alternates;
                if ($updated) {
                    $xml .= '<lastmod>'.$updated->toAtomString().'</lastmod>';
                }
                $xml .= '<priority>'.($i === 0 ? $priority : number_format(max(0.1, (float) $priority - 0.1), 1)).'</priority></url>'."\n";
            }
        }

        return $xml.'</urlset>'."\n";
    }

    /** Writes sitemap.xml to SITEMAP_OUTPUT_PATH (the frontend web root). Returns the path or null. */
    public function write(): ?string
    {
        $dir = config('cms.sitemap_output_path');
        if (! $dir || ! is_dir($dir) || ! is_writable($dir)) {
            return null;
        }

        $file = rtrim($dir, '/\\').DIRECTORY_SEPARATOR.'sitemap.xml';
        file_put_contents($file, $this->xml());

        return $file;
    }
}
