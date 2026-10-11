<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Faq;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\Industry;
use App\Models\MenuItem;
use App\Models\Milestone;
use App\Models\Office;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\SectionItem;
use App\Models\Service;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\Translation;
use App\Support\Html;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Starter Bengali translations for navigation, page titles and the seeded website
 * text (hero slides, sections, products, services, history, FAQs, settings...).
 * Matches on the exact English text, so content an editor has changed is left
 * alone, and never overwrites a translation that already exists. Long texts live in
 * database/seeders/data/bn-content.json. Have a native speaker review the wording.
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
        'Dealers & Distributors' => 'ডিলার ও ডিস্ট্রিবিউটর',
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

        $content = json_decode((string) file_get_contents(__DIR__.'/data/bn-content.json'), true) ?: [];
        $models = [
            HeroSlide::class => ['eyebrow', 'title', 'highlight', 'title_suffix', 'subtitle', 'primary_label', 'secondary_label'],
            PageSection::class => ['eyebrow', 'title', 'subtitle', 'content', 'button_label', 'image_alt'],
            SectionItem::class => ['title', 'subtitle', 'description', 'value'],
            Page::class => ['hero_title', 'hero_subtitle', 'content', 'meta_title', 'meta_description'],
            Product::class => ['name', 'short_description', 'description', 'meta_description'],
            ProductCategory::class => ['short_description'],
            Service::class => ['title', 'short_description', 'description'],
            NewsPost::class => ['title', 'excerpt', 'content', 'meta_title', 'meta_description'],
            NewsCategory::class => ['name'],
            GalleryAlbum::class => ['title', 'description'],
            GalleryImage::class => ['caption'],
            \App\Models\Dealer::class => ['name', 'address'],
            Milestone::class => ['title', 'description'],
            Faq::class => ['question', 'answer'],
            TeamMember::class => ['designation', 'quote', 'message'],
            Certification::class => ['name'],
            Testimonial::class => ['designation'],
            Industry::class => ['name'],
            Office::class => ['name', 'address'],
        ];
        foreach ($models as $class => $fields) {
            $this->translate($class::query(), $fields, $content);
        }

        // Text settings of sections ("Our Goal" card, "Years of Excellence" badge...).
        PageSection::query()->withoutGlobalScopes()->whereNotNull('settings')->get()->each(function (PageSection $section) use ($content) {
            foreach (PageSection::TRANSLATABLE_SETTINGS as $key) {
                $bn = $content[(string) ($section->getRawOriginal('settings') ? (json_decode($section->getRawOriginal('settings'), true)[$key] ?? '') : '')] ?? null;
                if ($bn) {
                    Translation::firstOrCreate(
                        ['translatable_type' => 'PageSection', 'translatable_id' => $section->id, 'locale' => 'bn', 'field' => 'settings:'.$key],
                        ['value' => $bn],
                    );
                }
            }
        });

        // Site-wide texts from Settings.
        foreach (config('cms.translatable_settings') as $key) {
            $setting = Setting::where('key', $key)->first();
            $bn = $setting ? ($content[(string) $setting->value] ?? null) : null;
            if ($bn) {
                Translation::firstOrCreate(
                    ['translatable_type' => 'Setting', 'translatable_id' => $setting->id, 'locale' => 'bn', 'field' => 'value'],
                    ['value' => mb_substr(strip_tags($bn), 0, 1000)],
                );
            }
        }
    }

    private function translate($query, array $fields, ?array $dictionary = null): void
    {
        $dictionary ??= self::TEXT;
        $query->withoutGlobalScopes()->get()->each(function (Model $model) use ($fields, $dictionary) {
            $casts = $model->getCasts();
            foreach ($fields as $field) {
                $bn = $dictionary[$model->getRawOriginal($field) ?? ''] ?? null;
                if (! $bn) {
                    continue;
                }
                if (($casts[$field] ?? null) === \App\Casts\SanitizedHtml::class) {
                    $bn = Html::clean($bn);
                }
                Translation::firstOrCreate(
                    ['translatable_type' => $model->getMorphClass(), 'translatable_id' => $model->getKey(), 'locale' => 'bn', 'field' => $field],
                    ['value' => $bn],
                );
            }
        });
    }
}
