<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    public function test_catalog_lists_published_products_by_category_order(): void
    {
        $caps = ProductCategory::create(['name' => 'Caps', 'slug' => 'caps', 'sort_order' => 2, 'is_active' => true]);
        $bottles = ProductCategory::create(['name' => 'Bottles', 'slug' => 'bottles', 'sort_order' => 1, 'is_active' => true]);
        Product::create(['name' => 'Cap A', 'slug' => 'cap-a', 'product_category_id' => $caps->id, 'status' => 'published']);
        $bottle = Product::create(['name' => 'Bottle A', 'slug' => 'bottle-a', 'product_category_id' => $bottles->id, 'status' => 'published']);
        $bottle->specifications()->create(['label' => 'Material', 'value' => 'PET', 'sort_order' => 1]);
        Product::create(['name' => 'Secret draft', 'slug' => 'secret', 'product_category_id' => $bottles->id, 'status' => 'draft']);

        $this->getJson('/api/v1/products/catalog')->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.name', 'Bottle A')
            ->assertJsonPath('0.specifications.0.value', 'PET')
            ->assertJsonPath('1.name', 'Cap A');
    }
}
