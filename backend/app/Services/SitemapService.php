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
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as [$loc, $updated, $priority]) {
            $xml .= '  <url><loc>'.htmlspecialchars($loc, ENT_XML1).'</loc>';
            if ($updated) {
                $xml .= '<lastmod>'.$updated->toAtomString().'</lastmod>';
            }
            $xml .= '<priority>'.$priority.'</priority></url>'."\n";
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
