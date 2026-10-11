<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\Translation;
use App\Services\SettingsService;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    public function test_product_translations_with_fallback(): void
    {
        $admin = $this->admin();

        $id = $this->actingAs($admin)->spa()->postJson('/api/v1/admin/products', [
            'name' => 'PET Bottle', 'short_description' => 'Clear bottle', 'status' => 'published',
            'description' => '<p>English</p>',
            'translations' => ['bn' => [
                'name' => 'পেট বোতল',
                'description' => '<p>বাংলা</p><script>alert(1)</script>',
                'short_description' => '',
                'sku' => 'not translatable',
            ], 'fr' => ['name' => 'Bouteille']],
        ])->assertCreated()->assertJsonPath('translations.bn.name', 'পেট বোতল')->json('id');

        $this->assertNull(Translation::where('field', 'sku')->first());
        $this->assertNull(Translation::where('locale', 'fr')->first());
        $this->assertStringNotContainsString('<script', Translation::where('field', 'description')->value('value'));

        $this->getJson('/api/v1/products/pet-bottle?lang=bn')->assertOk()
            ->assertHeader('Content-Language', 'bn')
            ->assertJsonPath('data.name', 'পেট বোতল')
            ->assertJsonPath('data.short_description', 'Clear bottle'); // empty translation falls back
        $this->getJson('/api/v1/products/pet-bottle')->assertJsonPath('data.name', 'PET Bottle');
        $this->getJson('/api/v1/products/pet-bottle?lang=xx')->assertJsonPath('data.name', 'PET Bottle');

        // Clearing a translation removes it.
        $this->actingAs($admin)->spa()->putJson("/api/v1/admin/products/{$id}", [
            'name' => 'PET Bottle', 'status' => 'published', 'translations' => ['bn' => ['name' => '']],
        ])->assertOk();
        $this->getJson('/api/v1/products/pet-bottle?lang=bn')->assertJsonPath('data.name', 'PET Bottle');
    }

    public function test_spec_and_variant_translations_survive_resave(): void
    {
        $admin = $this->admin();
        $payload = [
            'name' => 'Jar', 'status' => 'published',
            'specifications' => [['label' => 'Material', 'value' => 'PET', 'translations' => ['bn' => ['label' => 'উপাদান']]]],
            'variants' => [['name' => '500 ml', 'color' => 'Clear', 'translations' => ['bn' => ['color' => 'স্বচ্ছ']]]],
        ];
        $id = $this->actingAs($admin)->spa()->postJson('/api/v1/admin/products', $payload)->assertCreated()
            ->assertJsonPath('specifications.0.translations.bn.label', 'উপাদান')->json('id');
        $this->actingAs($admin)->spa()->putJson("/api/v1/admin/products/{$id}", $payload)->assertOk();

        $this->getJson('/api/v1/products/jar?lang=bn')
            ->assertJsonPath('data.specifications.0.label', 'উপাদান')
            ->assertJsonPath('data.specifications.0.value', 'PET')
            ->assertJsonPath('data.variants.0.color', 'স্বচ্ছ');
        $this->assertSame(2, Translation::count()); // old rows' translations were removed with them

        // Deleting the product also clears translations of its cascade-deleted rows.
        $this->actingAs($admin)->spa()->deleteJson("/api/v1/admin/products/{$id}")->assertNoContent();
        $this->assertSame(0, Translation::count());
    }

    public function test_deleting_page_or_section_clears_nested_translations(): void
    {
        $admin = $this->admin();
        $page = Page::create(['title' => 'X', 'slug' => 'x', 'status' => 'published']);
        $sectionId = $this->actingAs($admin)->spa()->postJson("/api/v1/admin/pages/{$page->id}/sections", [
            'type' => 'icon_cards', 'translations' => ['bn' => ['title' => 'ক']],
            'items' => [['title' => 'A', 'translations' => ['bn' => ['title' => 'খ']]], ['title' => 'B', 'translations' => ['bn' => ['title' => 'গ']]]],
        ])->json('id');
        $items = $this->actingAs($admin)->spa()->getJson("/api/v1/admin/sections/{$sectionId}")->json('items');

        // Removing an item from the list removes its translation.
        $this->actingAs($admin)->spa()->putJson("/api/v1/admin/sections/{$sectionId}", ['items' => [['id' => $items[0]['id'], 'title' => 'A']]])->assertOk();
        $this->assertSame(2, Translation::count());

        $this->actingAs($admin)->spa()->deleteJson("/api/v1/admin/sections/{$sectionId}")->assertNoContent();
        $this->assertSame(0, Translation::count());
    }

    public function test_page_sections_items_and_menus(): void
    {
        $admin = $this->admin();
        $page = Page::create(['title' => 'About', 'slug' => 'about', 'status' => 'published']);
        $page->syncTranslations(['bn' => ['title' => 'আমাদের সম্পর্কে']]);

        $this->actingAs($admin)->spa()->postJson("/api/v1/admin/pages/{$page->id}/sections", [
            'type' => 'icon_cards', 'title' => 'Our Values',
            'translations' => ['bn' => ['title' => 'আমাদের মূল্যবোধ']],
            'items' => [['icon' => 'leaf', 'title' => 'Quality', 'translations' => ['bn' => ['title' => 'গুণমান']]]],
        ])->assertCreated();

        MenuItem::create(['location' => 'header', 'label' => 'About', 'link_type' => 'page', 'page_id' => $page->id])
            ->syncTranslations(['bn' => ['label' => 'আমাদের কথা']]);

        $this->getJson('/api/v1/pages/about?lang=bn')
            ->assertJsonPath('title', 'আমাদের সম্পর্কে')
            ->assertJsonPath('sections.0.title', 'আমাদের মূল্যবোধ')
            ->assertJsonPath('sections.0.items.0.title', 'গুণমান');
        $this->getJson('/api/v1/navigation?lang=bn')->assertJsonPath('header.0.label', 'আমাদের কথা');
        $this->getJson('/api/v1/navigation')->assertJsonPath('header.0.label', 'About');

        // Admin always edits the default language and sees translations separately.
        $this->actingAs($admin)->spa()->getJson("/api/v1/admin/pages/{$page->id}/sections")
            ->assertJsonPath('0.title', 'Our Values')
            ->assertJsonPath('0.translations.bn.title', 'আমাদের মূল্যবোধ')
            ->assertJsonPath('0.items.0.translations.bn.title', 'গুণমান');
    }

    public function test_settings_translations_do_not_leak_into_default_cache(): void
    {
        $settings = app(SettingsService::class);
        $settings->save(['site_name' => 'IR Limited', 'footer_about' => 'English about', 'topbar_text' => 'English bar']);

        $this->actingAs($this->admin())->spa()->putJson('/api/v1/admin/settings', [
            'site_name' => 'IR Limited',
            'translations' => ['bn' => ['footer_about' => 'বাংলা বিবরণ', 'site_name' => 'not translatable', 'enquiry_notify_email' => 'x']],
        ])->assertOk()->assertJsonPath('translations.bn.footer_about', 'বাংলা বিবরণ')->assertJsonMissingPath('translations.bn.site_name');

        // Bengali request first, then English: English must not be served from a polluted cache.
        $this->getJson('/api/v1/settings?lang=bn')->assertJsonPath('footer_about', 'বাংলা বিবরণ')->assertJsonPath('topbar_text', 'English bar')->assertJsonPath('locale', 'bn');
        $this->getJson('/api/v1/settings')->assertJsonPath('footer_about', 'English about')->assertJsonPath('locale', 'en');
    }

    public function test_deleting_a_record_removes_its_translations(): void
    {
        $product = Product::create(['name' => 'Cap', 'status' => 'published']);
        $product->syncTranslations(['bn' => ['name' => 'ক্যাপ']]);

        $this->actingAs($this->admin())->spa()->deleteJson("/api/v1/admin/products/{$product->id}")->assertNoContent();

        $this->assertSame(0, Translation::count());
    }

    public function test_bangla_seeder_adds_missing_translations_without_overwriting(): void
    {
        $home = MenuItem::create(['location' => 'header', 'label' => 'Home', 'link_type' => 'url', 'url' => '/', 'is_active' => true]);
        $news = MenuItem::create(['location' => 'header', 'label' => 'News', 'link_type' => 'url', 'url' => '/news', 'is_active' => true]);
        $news->syncTranslations(['bn' => ['label' => 'খবর']]);

        $this->seed(\Database\Seeders\BanglaContentSeeder::class);
        $this->seed(\Database\Seeders\BanglaContentSeeder::class);

        $this->assertSame('হোম', $home->translationsArray()['bn']['label']);
        $this->assertSame('খবর', $news->translationsArray()['bn']['label']);
        $this->assertSame(2, Translation::count());
    }

    public function test_section_text_settings_can_be_translated(): void
    {
        $page = Page::create(['title' => 'Mission', 'slug' => 'mission', 'status' => 'published']);
        $section = $page->sections()->create(['type' => 'image_text', 'title' => 'Goal', 'settings' => ['card_title' => 'Our Goal', 'card_icon' => 'target'], 'is_active' => true]);

        $this->actingAs($this->admin())->spa()->putJson("/api/v1/admin/sections/{$section->id}", [
            'title' => 'Goal', 'settings' => ['card_title' => 'Our Goal', 'card_icon' => 'target'],
            'translations' => ['bn' => ['settings:card_title' => 'আমাদের লক্ষ্য', 'settings:card_icon' => 'x', 'settings:evil' => 'y']],
        ])->assertOk();

        $this->assertSame(['settings:card_title'], Translation::where('translatable_type', 'PageSection')->pluck('field')->all());

        $bn = collect($this->getJson('/api/v1/pages/mission?lang=bn')->assertOk()->json('sections'))->firstWhere('id', $section->id);
        $en = collect($this->getJson('/api/v1/pages/mission')->assertOk()->json('sections'))->firstWhere('id', $section->id);
        $this->assertSame('আমাদের লক্ষ্য', $bn['settings']['card_title']);
        $this->assertSame('target', $bn['settings']['card_icon']);
        $this->assertSame('Our Goal', $en['settings']['card_title']);
    }

    public function test_content_seeder_translates_seeded_text_once(): void
    {
        $slide = \App\Models\HeroSlide::create(['title' => 'Innovative', 'highlight' => 'Packaging', 'is_active' => true]);
        $edited = \App\Models\HeroSlide::create(['title' => 'Something an editor wrote', 'is_active' => true]);

        $this->seed(\Database\Seeders\BanglaContentSeeder::class);
        $count = Translation::count();
        $this->seed(\Database\Seeders\BanglaContentSeeder::class);

        $this->assertSame($count, Translation::count());
        $this->assertSame('উদ্ভাবনী', $slide->translationsArray()['bn']['title']);
        $this->assertSame([], $edited->translationsArray());
    }
}

