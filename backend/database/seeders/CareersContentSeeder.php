<?php

namespace Database\Seeders;

use App\Models\JobPost;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Careers page, footer link and one DRAFT sample job. Idempotent.
 * `php artisan db:seed --class=CareersContentSeeder --force`
 */
class CareersContentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Page::where('slug', 'careers')->exists()) {
            $page = Page::create([
                'title' => 'Careers', 'slug' => 'careers', 'template' => 'careers', 'status' => 'published',
                'hero_title' => 'Careers', 'hero_subtitle' => 'Build your future with IR Limited',
                'hero_image' => Media::where('path', 'seed/production-line.svg')->value('path'),
                'meta_description' => 'Current job openings at IR Limited, plastic packaging manufacturer in Gazipur, Bangladesh.',
            ]);
            $page->forceFill(['is_system' => true])->save();
            $page->sections()->create([
                'type' => 'icon_cards', 'eyebrow' => 'Why Join Us', 'title' => 'Grow With a Growing Company',
                'settings' => ['style' => 'strip'], 'sort_order' => 1,
            ])->items()->createMany([
                ['icon' => 'trending-up', 'title' => 'Career growth', 'description' => 'Learn modern manufacturing technology.', 'sort_order' => 1],
                ['icon' => 'users', 'title' => 'Great team', 'description' => 'Work with experienced professionals.', 'sort_order' => 2],
                ['icon' => 'shield-check', 'title' => 'Safe workplace', 'description' => 'Strict health & safety standards.', 'sort_order' => 3],
                ['icon' => 'award', 'title' => 'Recognition', 'description' => 'Good work is noticed and rewarded.', 'sort_order' => 4],
            ]);
            $page->sections()->create(['type' => 'module_content', 'eyebrow' => 'Open Positions', 'title' => 'Current Openings', 'sort_order' => 2]);

            MenuItem::create(['location' => 'footer_quick', 'label' => 'Careers', 'link_type' => 'page', 'page_id' => $page->id]);
        }

        if (JobPost::doesntExist()) {
            JobPost::create([
                'title' => 'Production Supervisor (Sample)',
                'department' => 'Production',
                'location' => 'Konabari, Gazipur',
                'employment_type' => 'full_time',
                'vacancies' => 2,
                'summary' => 'Sample job post (draft). Edit the details and publish it, or delete it.',
                'description' => '<h3>Responsibilities</h3><ul><li>Supervise blow and injection moulding lines</li><li>Ensure production targets and quality standards</li></ul><h3>Requirements</h3><ul><li>Diploma in Mechanical or Chemical Engineering</li><li>3+ years in plastic manufacturing</li></ul>',
                'status' => 'draft',
                'deadline' => now()->addMonth()->toDateString(),
            ]);
        }
    }
}
