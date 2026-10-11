<?php

namespace Database\Seeders;

use App\Models\GalleryAlbum;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Gallery, testimonials, management team and video content.
 * Idempotent: safe to run on an existing site (`php artisan db:seed --class=ShowcaseContentSeeder --force`).
 * Testimonials are seeded HIDDEN and the team page as DRAFT, so no invented content goes live.
 */
class ShowcaseContentSeeder extends Seeder
{
    protected function m(string $name): ?string
    {
        return Media::where('path', 'seed/'.$name.'.svg')->value('path');
    }

    public function run(): void
    {
        $this->gallery();
        $this->testimonials();
        $this->pages();
    }

    protected function gallery(): void
    {
        if (GalleryAlbum::exists()) {
            return;
        }

        $album = GalleryAlbum::create([
            'title' => 'Factory & Production',
            'description' => 'Our manufacturing facility in BSCIC Industrial Estate, Konabari, Gazipur. (Placeholder illustrations: replace with real photos.)',
            'cover_image' => $this->m('factory'),
        ]);
        foreach (['factory' => 'Head office & factory', 'production-line' => 'Bottle production line', 'quality-lab' => 'Quality control laboratory',
            'preform' => 'PET preforms', 'bottles' => 'PET & HDPE bottles', 'caps' => 'Caps & closures', 'jars' => 'Jars & containers'] as $file => $caption) {
            if ($path = $this->m($file)) {
                $album->images()->create(['path' => $path, 'caption' => $caption]);
            }
        }
    }

    protected function testimonials(): void
    {
        if (Testimonial::exists()) {
            return;
        }

        // Samples only (hidden). Publish real, approved client quotes instead.
        foreach ([
            ['Sample Client', 'Procurement Manager', 'Beverage Company', 'Replace this sample with a real quote from a client who has agreed to be featured.'],
            ['Sample Client', 'Operations Head', 'Pharmaceutical Company', 'Replace this sample with a real quote from a client who has agreed to be featured.'],
            ['Sample Client', 'Managing Director', 'Food Processing Company', 'Replace this sample with a real quote from a client who has agreed to be featured.'],
        ] as [$name, $designation, $company, $quote]) {
            Testimonial::create(compact('name', 'designation', 'company', 'quote') + ['rating' => 5, 'is_active' => false]);
        }
    }

    protected function pages(): void
    {
        $about = Page::where('slug', 'about')->first();
        $home = Page::where('is_home', true)->first();
        $aboutMenu = MenuItem::where('location', 'header')->whereNull('parent_id')->where('page_id', $about?->id)->first();

        if (! Page::where('slug', 'gallery')->exists()) {
            $gallery = Page::create([
                'title' => 'Gallery', 'slug' => 'gallery', 'template' => 'gallery', 'status' => 'published',
                'hero_title' => 'Gallery', 'hero_subtitle' => 'A look inside IR Limited', 'hero_image' => $this->m('factory-banner'),
                'meta_description' => 'Photos of the IR Limited factory, production lines, quality control and events.',
            ]);
            $gallery->forceFill(['is_system' => true])->save();
            $gallery->sections()->create(['type' => 'module_content', 'sort_order' => 1]);
            $gallery->sections()->create([
                'type' => 'cta_banner', 'title' => 'Visit Our Factory', 'subtitle' => 'Factory visits can be arranged by appointment.',
                'button_label' => 'Contact Us', 'button_url' => '/contact', 'image' => $this->m('production-line'), 'sort_order' => 2,
            ]);
            if ($aboutMenu) {
                MenuItem::create(['location' => 'header', 'parent_id' => $aboutMenu->id, 'label' => 'Gallery', 'link_type' => 'page', 'page_id' => $gallery->id]);
            }
        }

        if (! Page::where('slug', 'management-team')->exists()) {
            // Draft until real team members (with photos) are added under Team & Founder.
            $team = Page::create([
                'title' => 'Management Team', 'slug' => 'management-team', 'status' => 'draft',
                'hero_title' => 'Management Team', 'hero_subtitle' => 'The people leading IR Limited', 'hero_image' => $this->m('factory-banner'),
            ]);
            $team->sections()->create([
                'type' => 'team_grid', 'eyebrow' => 'Our Leadership', 'title' => 'Meet Our Management Team',
                'settings' => ['include_founder' => true], 'sort_order' => 1,
            ]);
            if ($aboutMenu) {
                MenuItem::create(['location' => 'header', 'parent_id' => $aboutMenu->id, 'label' => 'Management Team', 'link_type' => 'page', 'page_id' => $team->id]);
            }
        }

        foreach (array_filter([$about, $home]) as $page) {
            if ($page->sections()->where('type', 'testimonials')->doesntExist()) {
                $cta = $page->sections()->where('type', 'cta_banner')->orderByDesc('sort_order')->first();
                $position = $cta ? $cta->sort_order : (int) $page->sections()->max('sort_order') + 1;
                $page->sections()->where('sort_order', '>=', $position)->increment('sort_order');
                $page->sections()->create([
                    'type' => 'testimonials', 'eyebrow' => 'Client Feedback', 'title' => 'What Our Clients Say', 'sort_order' => $position,
                ]);
            }
        }

        if ($about && $about->sections()->where('type', 'video')->doesntExist()) {
            $cta = $about->sections()->where('type', 'cta_banner')->orderByDesc('sort_order')->first();
            $position = $cta ? $cta->sort_order : (int) $about->sections()->max('sort_order') + 1;
            $about->sections()->where('sort_order', '>=', $position)->increment('sort_order');
            // Hidden until a real factory video URL is added.
            $about->sections()->create([
                'type' => 'video', 'admin_label' => 'Factory video (add a YouTube link, then make visible)',
                'eyebrow' => 'Factory Tour', 'title' => 'See How We Work', 'subtitle' => 'Take a short tour of our production facility.',
                'image' => $this->m('production-line'), 'settings' => ['background' => 'dark'], 'is_active' => false, 'sort_order' => $position,
            ]);
        }
    }
}
