<?php

namespace Tests\Feature;

use App\Models\Industry;
use App\Models\Product;
use Tests\TestCase;

class ProductExtrasTest extends TestCase
{
    public function test_admin_saves_variants_and_industries(): void
    {
        $admin = $this->admin();
        $food = Industry::create(['name' => 'Food']);
        $pharma = Industry::create(['name' => 'Pharmaceutical']);

        $id = $this->actingAs($admin)->spa()->postJson('/api/v1/admin/products', [
            'name' => 'PET Jar', 'status' => 'published',
            'industry_ids' => [$food->id, $pharma->id],
            'variants' => [
                ['name' => '250 ml', 'capacity' => '250 ml', 'weight' => '14 g', 'moq' => '10,000'],
                ['name' => '500 ml', 'neck_size' => '63 mm'],
            ],
        ])->assertCreated()
            ->assertJsonCount(2, 'variants')
            ->assertJsonPath('variants.1.neck_size', '63 mm')
            ->json('id');

        $this->assertEqualsCanonicalizing([$food->id, $pharma->id], Product::find($id)->industries->pluck('id')->all());

        // Updating with a new list replaces variants and syncs industries.
        $this->actingAs($admin)->spa()->putJson("/api/v1/admin/products/{$id}", [
            'name' => 'PET Jar', 'status' => 'published', 'industry_ids' => [$food->id], 'variants' => [['name' => '1 L']],
        ])->assertOk()->assertJsonCount(1, 'variants')->assertJsonPath('industry_ids', [$food->id]);

        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/products', [
            'name' => 'Bad', 'status' => 'draft', 'industry_ids' => [9999], 'variants' => [['capacity' => 'no name']],
        ])->assertStatus(422)->assertJsonValidationErrors(['industry_ids.0', 'variants.0.name']);
    }

    public function test_public_industry_filter_and_listing(): void
    {
        $food = Industry::create(['name' => 'Food']);
        $hidden = Industry::create(['name' => 'Secret', 'is_active' => false]);
        $empty = Industry::create(['name' => 'Unused']);
        $jar = Product::create(['name' => 'Jar', 'status' => 'published']);
        $draft = Product::create(['name' => 'Draft jar', 'status' => 'draft']);
        $cap = Product::create(['name' => 'Cap', 'status' => 'published']);
        $jar->industries()->sync([$food->id, $hidden->id]);
        $draft->industries()->sync([$food->id]);
        $cap->industries()->sync([$hidden->id]);

        $this->getJson('/api/v1/industries')->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.slug', 'food')->assertJsonPath('0.products_count', 1);

        $this->getJson('/api/v1/products?industry=food')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Jar');
        $this->getJson('/api/v1/products?industry=secret')->assertJsonCount(0, 'data');

        // Inactive industries are not shown on the public product page.
        $this->getJson('/api/v1/products/jar')->assertJsonCount(1, 'data.industries')->assertJsonPath('data.industries.0.slug', 'food');
        $this->assertNotNull($empty);
    }

    public function test_compare_returns_published_products_in_requested_order(): void
    {
        Product::create(['name' => 'Alpha', 'status' => 'published'])->specifications()->create(['label' => 'Material', 'value' => 'PET']);
        Product::create(['name' => 'Beta', 'status' => 'published']);
        Product::create(['name' => 'Hidden', 'status' => 'draft']);
        foreach (['c1', 'c2', 'c3'] as $n) {
            Product::create(['name' => $n, 'status' => 'published']);
        }

        $this->getJson('/api/v1/products/compare?slugs=beta,alpha,hidden,unknown')->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.slug', 'beta')
            ->assertJsonPath('1.specifications.0.value', 'PET');

        // At most four products.
        $this->getJson('/api/v1/products/compare?slugs=alpha,beta,c1,c2,c3')->assertJsonCount(4);
        $this->getJson('/api/v1/products/compare')->assertStatus(422);
    }
}
