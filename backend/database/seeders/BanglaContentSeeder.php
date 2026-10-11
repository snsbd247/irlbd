<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Starter Bengali translations for navigation and page titles, so the language
 * switch shows something meaningful out of the box. Matches on the English text and
 * never overwrites a translation an editor has already entered. Have a native
 * speaker review the wording before launch.
 */
class BanglaContentSeeder extends Seeder
{
    private const TEXT = [
        'Home' => 'হোম',
        'About' => 'আমাদের সম্পর্কে',
        'About Us' => 'আমাদের সম্পর্কে',
        'Founder Message' => 'প্রতিষ্ঠাতার বার্তা',
        'Our Mission' => 'আমাদের লক্ষ্য',
        'Our Vision' => 'আমাদের দৃষ্টিভঙ্গি',
        'History' => 'ইতিহাস',
        'Quality Control' => 'মান নিয়ন্ত্রণ',
        'Our Products' => 'আমাদের পণ্য',
        'News' => 'সংবাদ',
        'News & Notice' => 'সংবাদ ও নোটিশ',
        'Contact Us' => 'যোগাযোগ',
        'Privacy Policy' => 'গোপনীয়তা নীতি',
        'Gallery' => 'গ্যালারি',
        'Management Team' => 'ব্যবস্থাপনা পর্ষদ',
        'Careers' => 'ক্যারিয়ার',
        'Plastic Bottles' => 'প্লাস্টিক বোতল',
        'Plastic Caps & Closures' => 'প্লাস্টিক ক্যাপ ও ক্লোজার',
        'Jars & Containers' => 'জার ও কন্টেইনার',
    ];

    public function run(): void
    {
        $this->translate(MenuItem::query(), ['label']);
        $this->translate(Page::query(), ['title', 'hero_title']);
        $this->translate(ProductCategory::query(), ['name']);
        $this->translate(NewsCategory::query(), ['name']);
    }

    private function translate($query, array $fields): void
    {
        $query->get()->each(function (Model $model) use ($fields) {
            foreach ($fields as $field) {
                $bn = self::TEXT[$model->getRawOriginal($field) ?? ''] ?? null;
                if (! $bn) {
                    continue;
                }
                Translation::firstOrCreate(
                    ['translatable_type' => $model->getMorphClass(), 'translatable_id' => $model->getKey(), 'locale' => 'bn', 'field' => $field],
                    ['value' => $bn],
                );
            }
        });
    }
}
