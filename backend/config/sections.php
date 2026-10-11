<?php

/*
|--------------------------------------------------------------------------
| Approved page section types
|--------------------------------------------------------------------------
| Only these section types can be created by the page/homepage builder.
| "fields"   - which page_sections columns the type uses
| "items"    - which section_items columns are editable (null = no items)
| "settings" - allowed JSON settings keys and their validation rules
| "source"   - data the API attaches automatically from another module
*/

return [
    'hero_slider' => [
        'label' => 'Hero slider',
        'description' => 'Full-width slider. Slides are managed under Homepage › Hero Slides.',
        'fields' => [],
        'items' => null,
        'settings' => ['autoplay' => 'boolean', 'interval' => 'integer|min:3|max:20'],
        'source' => 'hero_slides',
    ],
    'feature_strip' => [
        'label' => 'Feature strip',
        'description' => 'Light band of short icon highlights below the hero.',
        'fields' => [],
        'items' => ['icon', 'title', 'subtitle'],
        'settings' => [],
    ],
    'about_intro' => [
        'label' => 'About intro with image & stats',
        'description' => 'Image (optional video link) with text, button and statistic cards.',
        'fields' => ['eyebrow', 'title', 'content', 'image', 'image_alt', 'video_url', 'button_label', 'button_url'],
        'items' => ['icon', 'value', 'title'],
        'settings' => ['badge_value' => 'nullable|string|max:20', 'badge_label' => 'nullable|string|max:60'],
    ],
    'services_grid' => [
        'label' => 'Services grid',
        'description' => 'Cards from the Services module.',
        'fields' => ['eyebrow', 'title', 'subtitle'],
        'items' => null,
        'settings' => ['limit' => 'integer|min:1|max:24'],
        'source' => 'services',
    ],
    'product_categories' => [
        'label' => 'Product categories',
        'description' => 'Category cards from the Products module.',
        'fields' => ['eyebrow', 'title', 'subtitle', 'button_label', 'button_url'],
        'items' => null,
        'settings' => ['layout' => 'in:cards,banners'],
        'source' => 'product_categories',
    ],
    'featured_products' => [
        'label' => 'Featured products',
        'description' => 'Products marked as featured.',
        'fields' => ['eyebrow', 'title', 'subtitle', 'button_label', 'button_url'],
        'items' => null,
        'settings' => ['limit' => 'integer|min:1|max:24'],
        'source' => 'featured_products',
    ],
    'process_steps' => [
        'label' => 'Process steps',
        'description' => 'Numbered horizontal process (manufacturing, quality inspection...).',
        'fields' => ['eyebrow', 'title', 'subtitle'],
        'items' => ['icon', 'title', 'description'],
        'settings' => ['background' => 'in:white,light'],
    ],
    'clients' => [
        'label' => 'Client logos',
        'description' => 'Logos from the Clients module.',
        'fields' => ['eyebrow', 'title', 'subtitle'],
        'items' => null,
        'settings' => [],
        'source' => 'clients',
    ],
    'latest_news' => [
        'label' => 'Latest news',
        'description' => 'Most recent published news posts.',
        'fields' => ['eyebrow', 'title', 'subtitle', 'button_label', 'button_url'],
        'items' => null,
        'settings' => ['limit' => 'integer|min:1|max:12'],
        'source' => 'latest_news',
    ],
    'image_text' => [
        'label' => 'Image + text',
        'description' => 'Heading, rich text and image with optional highlight items and overlay card.',
        'fields' => ['eyebrow', 'title', 'content', 'image', 'image_alt', 'video_url', 'button_label', 'button_url'],
        'items' => ['icon', 'title', 'description'],
        'settings' => [
            'image_position' => 'in:left,right',
            'item_style' => 'in:side_cards,inline,checklist,icons_row',
            'card_icon' => 'nullable|string|max:50',
            'card_title' => 'nullable|string|max:80',
            'card_text' => 'nullable|string|max:300',
            'background' => 'in:white,light',
        ],
    ],
    'icon_cards' => [
        'label' => 'Icon cards',
        'description' => 'Values, focus areas or feature cards.',
        'fields' => ['eyebrow', 'title', 'subtitle'],
        'items' => ['icon', 'title', 'description'],
        'settings' => ['style' => 'in:strip,cards,split', 'columns' => 'integer|min:2|max:6'],
    ],
    'stats' => [
        'label' => 'Statistics band',
        'description' => 'Large numbers with labels.',
        'fields' => [],
        'items' => ['icon', 'value', 'title'],
        'settings' => [],
    ],
    'founder_message' => [
        'label' => 'Founder message',
        'description' => 'Shows the active founder from Team & Founder.',
        'fields' => ['eyebrow'],
        'items' => null,
        'settings' => [],
        'source' => 'founder',
    ],
    'timeline' => [
        'label' => 'History timeline',
        'description' => 'Milestones from the History module. Subtitle shows in the highlight box.',
        'fields' => ['eyebrow', 'title', 'content', 'subtitle'],
        'items' => null,
        'settings' => [],
        'source' => 'milestones',
    ],
    'certifications' => [
        'label' => 'Certifications',
        'description' => 'Only certifications marked "verified" are shown.',
        'fields' => ['eyebrow', 'title', 'subtitle'],
        'items' => null,
        'settings' => [],
        'source' => 'certifications',
    ],
    'faq' => [
        'label' => 'FAQ',
        'description' => 'Questions from Contact › FAQs.',
        'fields' => ['eyebrow', 'title'],
        'items' => null,
        'settings' => [],
        'source' => 'faqs',
    ],
    'gallery' => [
        'label' => 'Photo gallery',
        'description' => 'Recent albums from the Gallery module (factory, events, machinery...).',
        'fields' => ['eyebrow', 'title', 'subtitle', 'button_label', 'button_url'],
        'items' => null,
        'settings' => ['limit' => 'integer|min:1|max:12'],
        'source' => 'gallery',
    ],
    'testimonials' => [
        'label' => 'Testimonials',
        'description' => 'Client feedback from Clients › Testimonials (only visible ones).',
        'fields' => ['eyebrow', 'title', 'subtitle'],
        'items' => null,
        'settings' => [],
        'source' => 'testimonials',
    ],
    'team_grid' => [
        'label' => 'Management team',
        'description' => 'Cards for active members from Team & Founder.',
        'fields' => ['eyebrow', 'title', 'subtitle'],
        'items' => null,
        'settings' => ['include_founder' => 'boolean'],
        'source' => 'team',
    ],
    'video' => [
        'label' => 'Video',
        'description' => 'YouTube or Vimeo video with a cover image. Plays in a pop-up.',
        'fields' => ['eyebrow', 'title', 'subtitle', 'image', 'image_alt', 'video_url'],
        'items' => null,
        'settings' => ['background' => 'in:white,light,dark'],
    ],
    'module_content' => [
        'label' => 'Module content (catalog / news list / contact form)',
        'description' => 'Marks where the page module renders on Products, News and Contact pages. Without it the module renders after all sections.',
        'fields' => ['eyebrow', 'title', 'subtitle'],
        'items' => null,
        'settings' => [],
    ],
    'rich_text' => [
        'label' => 'Rich text',
        'description' => 'Free-form sanitized text content.',
        'fields' => ['eyebrow', 'title', 'content'],
        'items' => null,
        'settings' => [],
    ],
    'cta_banner' => [
        'label' => 'Call-to-action banner',
        'description' => 'Blue band with button. Empty fields fall back to General Settings › CTA.',
        'fields' => ['eyebrow', 'title', 'subtitle', 'image', 'button_label', 'button_url'],
        'items' => null,
        'settings' => [],
    ],
    'newsletter' => [
        'label' => 'Newsletter signup',
        'description' => 'Email subscription box.',
        'fields' => ['title', 'subtitle'],
        'items' => null,
        'settings' => [],
    ],
];
