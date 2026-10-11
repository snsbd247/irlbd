<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * "Our Branches" page (dealer-finder template, draft) plus a footer link that stays
 * hidden until the page is published. Idempotent; also recognises the earlier
 * "dealers" slug.
 */
class DealersContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Page::whereIn('slug', ['branches', 'dealers'])->exists()) {
            return;
        }

        $page = Page::create([
            'title' => 'Our Branches', 'slug' => 'branches', 'template' => 'dealers', 'status' => 'draft',
            'hero_title' => 'Our Branches', 'hero_subtitle' => 'Visit our head office in Dhaka or our factory in Gazipur',
            'hero_image' => Media::where('path', 'seed/production-line.svg')->value('path'),
            'meta_description' => 'IR Limited branches: head office in Dhanmondi, Dhaka and factory in BSCIC Industrial Estate, Konabari, Gazipur.',
        ]);
        $page->sections()->create(['type' => 'module_content', 'eyebrow' => 'Visit Us', 'title' => 'Our Branches', 'sort_order' => 1]);

        MenuItem::create(['location' => 'footer_quick', 'label' => 'Our Branches', 'link_type' => 'page', 'page_id' => $page->id]);
    }
}
