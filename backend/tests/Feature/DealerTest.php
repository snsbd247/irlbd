<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Models\Page;
use Tests\TestCase;

class DealerTest extends TestCase
{
    public function test_admin_manages_dealers_with_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/dealers', [
            'name' => 'X', 'type' => 'king', 'district' => 'Atlantis', 'map_url' => 'javascript:alert(1)',
        ])->assertUnprocessable()->assertJsonValidationErrors(['type', 'district', 'map_url']);

        $this->actingAs($admin)->spa()->postJson('/api/v1/admin/dealers', [
            'name' => 'Rahman Traders', 'type' => 'distributor', 'district' => "Cox's Bazar", 'phone' => '01711000000', 'is_active' => true,
            'translations' => ['bn' => ['name' => 'রহমান ট্রেডার্স']],
        ])->assertCreated()->assertJsonPath('translations.bn.name', 'রহমান ট্রেডার্স');
    }

    public function test_public_list_shows_active_dealers_and_filters_by_district(): void
    {
        Dealer::create(['name' => 'Gazipur Plastics', 'type' => 'dealer', 'district' => 'Gazipur', 'is_active' => true]);
        Dealer::create(['name' => 'Dhaka Packaging', 'type' => 'distributor', 'district' => 'Dhaka', 'is_active' => true]);
        Dealer::create(['name' => 'Hidden Co', 'type' => 'dealer', 'district' => 'Sylhet', 'is_active' => false]);

        $this->getJson('/api/v1/dealers')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('districts', ['Dhaka', 'Gazipur']);

        $this->getJson('/api/v1/dealers?district=Gazipur')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gazipur Plastics')
            ->assertJsonMissingPath('data.0.translations');
    }

    public function test_dealers_page_template_is_allowed(): void
    {
        $this->actingAs($this->admin())->spa()->postJson('/api/v1/admin/pages', [
            'title' => 'Our Dealers', 'template' => 'dealers', 'status' => 'published',
        ])->assertCreated();

        $this->assertSame('dealers', Page::where('title', 'Our Dealers')->value('template'));
    }

    public function test_seeded_dealers_page_stays_hidden_until_published(): void
    {
        $this->seed(\Database\Seeders\DealersContentSeeder::class);

        $this->getJson('/api/v1/pages/dealers')->assertNotFound();
        $footer = collect($this->getJson('/api/v1/navigation')->json('footer_quick'))->pluck('label');
        $this->assertNotContains('Dealers & Distributors', $footer);
    }
}
