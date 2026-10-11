<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * "Dealers & Distributors" page as a DRAFT plus a footer link (hidden until the page
 * is published). Add dealers in the admin, then publish the page. Idempotent.
 */
class DealersContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Page::where('slug', 'dealers')->exists()) {
            return;
        }

        $page = Page::create([
            'title' => 'Dealers & Distributors', 'slug' => 'dealers', 'template' => 'dealers', 'status' => 'draft',
            'hero_title' => 'Dealers & Distributors', 'hero_subtitle' => 'Find an IR Limited dealer near you',
            'hero_image' => Media::where('path', 'seed/production-line.svg')->value('path'),
            'meta_description' => 'Find IR Limited dealers and distributors of plastic bottles, caps and containers across Bangladesh.',
        ]);
        $page->sections()->create(['type' => 'module_content', 'eyebrow' => 'Across Bangladesh', 'title' => 'Find a Dealer Near You', 'sort_order' => 1]);

        MenuItem::create(['location' => 'footer_quick', 'label' => 'Dealers & Distributors', 'link_type' => 'page', 'page_id' => $page->id]);
    }
}
