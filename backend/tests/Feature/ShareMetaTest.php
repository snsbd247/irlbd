<?php

namespace Tests\Feature;

use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Product;
use App\Services\SettingsService;
use Tests\TestCase;

class ShareMetaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(SettingsService::class)->save(['site_name' => 'IR Limited', 'seo_title' => 'IR Limited | Packaging', 'seo_description' => 'Default description']);
    }

    public function test_meta_for_pages_products_and_news(): void
    {
        Page::create(['title' => 'Home', 'slug' => 'home', 'status' => 'published', 'is_home' => true]);
        Page::create(['title' => 'Our Mission', 'slug' => 'our-mission', 'status' => 'published', 'hero_subtitle' => 'Quality first']);
        Product::create(['name' => 'PET Bottle', 'status' => 'published', 'short_description' => 'Clear bottles']);
        NewsPost::create(['title' => 'Big News', 'status' => 'published', 'content' => '<p>Long <b>story</b></p>']);

        $this->getJson('/api/v1/meta?path=/')->assertOk()->assertJsonPath('title', 'IR Limited | Packaging')->assertJsonPath('url', 'http://localhost:5173/');
        $this->getJson('/api/v1/meta?path=/our-mission')->assertJsonPath('title', 'Our Mission | IR Limited')->assertJsonPath('description', 'Quality first');
        $this->getJson('/api/v1/meta?path=/products/pet-bottle')->assertJsonPath('title', 'PET Bottle | IR Limited')->assertJsonPath('type', 'product');
        $this->getJson('/api/v1/meta?path=/news/big-news')->assertJsonPath('description', 'Long story')->assertJsonPath('type', 'article');
    }

    public function test_drafts_and_unknown_paths_fall_back_to_defaults(): void
    {
        Product::create(['name' => 'Secret', 'status' => 'draft']);

        $this->getJson('/api/v1/meta?path=/products/secret')->assertOk()->assertJsonPath('title', 'IR Limited | Packaging')->assertJsonPath('description', 'Default description');
        $this->getJson('/api/v1/meta?path=/does/not/exist')->assertOk()->assertJsonPath('title', 'IR Limited | Packaging');
    }

    public function test_malformed_paths_are_rejected(): void
    {
        $this->getJson('/api/v1/meta?path='.urlencode('/products/<script>'))->assertStatus(422);
        $this->getJson('/api/v1/meta?path='.urlencode('/../../etc/passwd'))->assertStatus(422);
        // Hosts/dots are never valid site paths.
        $this->getJson('/api/v1/meta?path='.urlencode('//evil.example'))->assertStatus(422);
    }
}
