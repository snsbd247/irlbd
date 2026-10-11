<?php

namespace Tests\Feature;

use App\Models\Certification;
use App\Models\ContactSubmission;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\SettingsService;
use Database\Seeders\ContentSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    public function test_full_seeded_site_is_served(): void
    {
        Storage::fake('uploads');
        $this->seed(ContentSeeder::class);

        $this->getJson('/api/v1/home')->assertOk()
            ->assertJsonPath('is_home', true)
            ->assertJsonPath('sections.0.type', 'hero_slider')
            ->assertJsonCount(2, 'sections.0.data');

        foreach (['about', 'founder-message', 'our-mission', 'our-vision', 'history', 'quality-control', 'products', 'news', 'contact', 'privacy-policy'] as $slug) {
            $this->getJson('/api/v1/pages/'.$slug)->assertOk();
        }

        $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('meta.total', 8);
        $this->getJson('/api/v1/products?category=plastic-caps-closures')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/products/pet-water-bottle')->assertOk()->assertJsonCount(5, 'data.specifications');
        $this->getJson('/api/v1/news?per_page=3')->assertOk()->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/v1/navigation')->assertOk()->assertJsonCount(5, 'header')->assertJsonCount(6, 'header.1.children'); // + Gallery; draft Management Team stays hidden
    }

    public function test_drafts_and_inactive_content_are_hidden(): void
    {
        Page::create(['title' => 'Draft page', 'slug' => 'draft-page', 'status' => 'draft']);
        NewsPost::create(['title' => 'Draft news', 'status' => 'draft']);
        NewsPost::create(['title' => 'Future news', 'status' => 'published', 'published_at' => now()->addWeek()]);
        $hidden = ProductCategory::create(['name' => 'Hidden', 'is_active' => false]);
        Product::create(['name' => 'In hidden category', 'status' => 'published', 'product_category_id' => $hidden->id]);
        Product::create(['name' => 'Draft product', 'slug' => 'draft-product', 'status' => 'draft']);

        $this->getJson('/api/v1/pages/draft-page')->assertNotFound();
        $this->getJson('/api/v1/news')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/products')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/products/draft-product')->assertNotFound();
        $this->getJson('/api/v1/product-categories')->assertJsonCount(0);
        $this->getJson('/api/v1/search?q=draft')->assertJsonCount(0, 'results');
    }

    public function test_unverified_certifications_are_not_public(): void
    {
        $page = Page::create(['title' => 'QC', 'slug' => 'qc', 'status' => 'published']);
        $page->sections()->create(['type' => 'certifications', 'title' => 'Standards']);
        Certification::create(['name' => 'ISO (unverified)', 'is_verified' => false]);
        Certification::create(['name' => 'Verified cert', 'is_verified' => true]);

        $this->getJson('/api/v1/pages/qc')->assertOk()
            ->assertJsonCount(1, 'sections.0.data')
            ->assertJsonPath('sections.0.data.0.name', 'Verified cert');
    }

    public function test_private_settings_are_not_exposed(): void
    {
        app(SettingsService::class)->save(['site_name' => 'IR', 'enquiry_notify_email' => 'secret@example.com']);

        $this->getJson('/api/v1/settings')->assertOk()
            ->assertJsonPath('site_name', 'IR')
            ->assertJsonMissingPath('enquiry_notify_email');
    }

    public function test_enquiries_are_never_public(): void
    {
        ContactSubmission::create(['name' => 'A', 'email' => 'a@example.com', 'subject' => 'Other', 'message' => 'Private message']);

        $this->getJson('/api/v1/enquiries')->assertNotFound();
        $this->getJson('/api/v1/contact')->assertStatus(405);
        $this->getJson('/api/v1/admin/enquiries')->assertUnauthorized();
    }

    public function test_admin_only_fields_are_hidden_publicly(): void
    {
        Product::create(['name' => 'Bottle', 'status' => 'published']);

        $this->getJson('/api/v1/products')->assertJsonMissingPath('data.0.status');
    }

    public function test_sitemap_lists_published_content(): void
    {
        Page::create(['title' => 'Public', 'slug' => 'public', 'status' => 'published']);
        Page::create(['title' => 'Private', 'slug' => 'private', 'status' => 'draft']);

        $xml = $this->get('/api/v1/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $this->assertStringContainsString('http://localhost:5173/public', $xml);
        $this->assertStringNotContainsString('/private', $xml);
    }

    public function test_errors_do_not_leak_stack_traces(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/v1/products/unknown-slug')->assertNotFound();

        $this->assertArrayNotHasKey('trace', $response->json());
        $this->assertArrayNotHasKey('exception', $response->json());
    }
}
