<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use Tests\TestCase;

class CrudTest extends TestCase
{
    public function test_product_crud_with_gallery_and_specifications(): void
    {
        $admin = $this->admin();
        $image = $this->mediaPath('products/bottle.png');
        $category = ProductCategory::create(['name' => 'Bottles']);

        $id = $this->actingAs($admin)->spa()->postJson('/api/v1/admin/products', [
            'name' => 'PET Bottle',
            'product_category_id' => $category->id,
            'status' => 'published',
            'image' => $image,
            'is_featured' => true,
            'images' => [['path' => $image, 'alt' => 'Front']],
            'specifications' => [['label' => 'Material', 'value' => 'PET'], ['label' => 'Size', 'value' => '500 ml']],
        ])->assertCreated()
            ->assertJsonPath('slug', 'pet-bottle')
            ->assertJsonCount(2, 'specifications')
            ->assertJsonCount(1, 'images')
            ->json('id');

        $this->actingAs($admin)->spa()->putJson("/api/v1/admin/products/{$id}", [
            'name' => 'PET Bottle 1L', 'status' => 'published',
            'specifications' => [['label' => 'Material', 'value' => 'PET']],
        ])->assertOk()->assertJsonPath('name', 'PET Bottle 1L')->assertJsonCount(1, 'specifications');

        $this->actingAs($admin)->spa()->getJson('/api/v1/admin/products?search=1L')->assertJsonCount(1, 'data');

        $this->actingAs($admin)->spa()->deleteJson("/api/v1/admin/products/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('products', ['id' => $id]);
        $this->assertDatabaseCount('product_specifications', 0);
    }

    public function test_validation_errors_are_returned(): void
    {
        $this->actingAs($this->admin())->spa()->postJson('/api/v1/admin/products', ['status' => 'bogus', 'image' => 'not/in/library.png'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status', 'image']);
    }

    public function test_slugs_are_unique(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/services', ['title' => 'Molding'])->assertCreated()->assertJsonPath('slug', 'molding');
        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/services', ['title' => 'Molding'])->assertCreated()->assertJsonPath('slug', 'molding-2');
    }

    public function test_reorder_updates_sort_order(): void
    {
        $admin = $this->admin();
        $a = ProductCategory::create(['name' => 'A']);
        $b = ProductCategory::create(['name' => 'B']);

        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/product-categories/reorder', ['ids' => [$b->id, $a->id]])->assertOk();

        $this->assertSame(1, $b->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
    }

    public function test_page_builder_sections_and_items(): void
    {
        $admin = $this->admin();
        $page = Page::create(['title' => 'About', 'status' => 'published']);

        $sectionId = $this->actingAs($admin)->spa()->postJson("/api/v1/admin/pages/{$page->id}/sections", [
            'type' => 'icon_cards',
            'title' => 'Our Values',
            'settings' => ['style' => 'cards', 'columns' => 4, 'not_allowed' => 'dropped'],
            'items' => [['icon' => 'leaf', 'title' => 'Green'], ['icon' => 'gem', 'title' => 'Quality']],
        ])->assertCreated()
            ->assertJsonPath('settings.style', 'cards')
            ->assertJsonMissingPath('settings.not_allowed')
            ->assertJsonCount(2, 'items')
            ->json('id');

        $items = $this->actingAs($admin)->spa()->getJson("/api/v1/admin/sections/{$sectionId}")->json('items');

        // Keep the second item (by id), drop the first, add a new one.
        $this->actingAs($admin)->spa()->putJson("/api/v1/admin/sections/{$sectionId}", [
            'title' => 'Values',
            'is_active' => false,
            'items' => [['id' => $items[1]['id'], 'icon' => 'gem', 'title' => 'Quality+'], ['icon' => 'users', 'title' => 'People']],
        ])->assertOk()->assertJsonPath('items.0.title', 'Quality+')->assertJsonPath('items.0.id', $items[1]['id'])->assertJsonCount(2, 'items');

        // Hidden sections are not served publicly.
        $this->getJson('/api/v1/pages/'.$page->slug)->assertOk()->assertJsonCount(0, 'sections');
    }

    public function test_unknown_section_type_and_icon_are_rejected(): void
    {
        $page = Page::create(['title' => 'X', 'status' => 'published']);

        $this->actingAs($this->admin())->spa()->postJson("/api/v1/admin/pages/{$page->id}/sections", [
            'type' => 'custom_html',
            'items' => [['icon' => 'skull']],
        ])->assertStatus(422)->assertJsonValidationErrors(['type', 'items.0.icon']);
    }

    public function test_system_pages_cannot_be_deleted(): void
    {
        $page = Page::create(['title' => 'Products', 'template' => 'products', 'status' => 'published']);
        $page->forceFill(['is_system' => true])->save();

        $this->actingAs($this->admin())->spa()->deleteJson("/api/v1/admin/pages/{$page->id}")->assertStatus(422);
    }

    public function test_menu_items_two_levels(): void
    {
        $admin = $this->admin();
        $page = Page::create(['title' => 'Mission', 'status' => 'published']);

        $parent = $this->actingAs($admin)->spa()->postJson('/api/v1/admin/menu-items', [
            'location' => 'header', 'label' => 'About', 'link_type' => 'url', 'url' => '/about',
        ])->assertCreated()->json('id');

        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/menu-items', [
            'location' => 'header', 'label' => 'Mission', 'link_type' => 'page', 'page_id' => $page->id, 'parent_id' => $parent,
        ])->assertCreated()->assertJsonPath('href', '/mission');

        $this->getJson('/api/v1/navigation')->assertOk()
            ->assertJsonPath('header.0.label', 'About')
            ->assertJsonPath('header.0.children.0.href', '/mission');

        $this->assertSame(2, MenuItem::count());
    }

    public function test_news_post_records_author_and_publish_date(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/news-posts', [
            'title' => 'Launch', 'status' => 'published', 'content' => '<p>Hello</p>',
        ])->assertCreated()->assertJsonPath('author', $admin->name);

        $this->assertNotNull(\App\Models\NewsPost::first()->published_at);
    }

    public function test_product_index_filters_by_status(): void
    {
        Product::create(['name' => 'A', 'status' => 'draft']);
        Product::create(['name' => 'B', 'status' => 'published']);

        $this->actingAs($this->admin())->spa()->getJson('/api/v1/admin/products?status=published')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'B');
    }
}
