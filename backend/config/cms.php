<?php

/*
|--------------------------------------------------------------------------
| CMS definitions
|--------------------------------------------------------------------------
| Single source of truth for editable settings, permission modules, the icon
| allow-list and upload limits. The admin UI renders its forms from these
| definitions (GET /api/v1/admin/meta) and the API validates against them.
*/

return [

    // Used once by the seeder / installer to create the first super admin. Remove from .env afterwards.
    'initial_admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Enables the one-time web installer (POST /install) for hosts without SSH. Leave empty otherwise.
    'install_token' => env('INSTALL_TOKEN'),

    'seed_demo_content' => (bool) env('SEED_DEMO_CONTENT', true),

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    // Absolute path where the generated sitemap.xml should be written
    // (usually the frontend document root, e.g. /home/user/public_html).
    'sitemap_output_path' => env('SITEMAP_OUTPUT_PATH'),

    'uploads' => [
        'max_kb' => (int) env('UPLOAD_MAX_KB', 5120),
        'max_dimension' => (int) env('UPLOAD_MAX_DIMENSION', 2000),
        // Raster images only. SVG is intentionally excluded (it can carry script).
        'mimes' => [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
        ],
        'folders' => ['general', 'branding', 'pages', 'products', 'news', 'team', 'clients', 'certifications', 'history', 'slides'],
    ],

    'permission_modules' => [
        'settings' => 'General settings & SEO defaults',
        'menus' => 'Menus & navigation',
        'pages' => 'Pages, sections & homepage builder',
        'products' => 'Products & categories',
        'services' => 'Services',
        'clients' => 'Clients',
        'team' => 'Team & founder',
        'news' => 'News & categories',
        'history' => 'History milestones',
        'quality' => 'Quality control & certifications',
        'contact' => 'Offices & FAQs',
        'enquiries' => 'Contact enquiries',
        'subscribers' => 'Newsletter subscribers',
        'media' => 'Media library',
        'users' => 'Admin users & roles',
    ],

    'icons' => [
        'award', 'badge-check', 'bar-chart', 'bottle', 'box', 'boxes', 'building', 'calendar', 'check-circle',
        'clipboard-check', 'clock', 'container', 'cpu', 'cylinder', 'droplet', 'eye', 'factory', 'flask',
        'gem', 'globe', 'hammer', 'handshake', 'headphones', 'heart', 'layers', 'leaf', 'lightbulb', 'mail',
        'map-pin', 'microscope', 'package', 'pen-tool', 'phone', 'recycle', 'rocket', 'ruler', 'scan',
        'settings', 'shield-check', 'smile', 'sparkles', 'star', 'target', 'thermometer', 'trending-up',
        'trophy', 'truck', 'users', 'wrench', 'zap',
    ],

    /*
    | Setting definitions: key => [group, type, label, rules, public]
    | Types: text, textarea, email, url, image, boolean, map
    */
    'settings' => [
        // Branding
        'site_name' => ['branding', 'text', 'Site name', 'required|string|max:120', true],
        'site_tagline' => ['branding', 'text', 'Tagline (under logo)', 'nullable|string|max:150', true],
        'logo_header' => ['branding', 'image', 'Header logo', 'nullable|string|max:255', true],
        'logo_footer' => ['branding', 'image', 'Footer logo', 'nullable|string|max:255', true],
        'logo_light' => ['branding', 'image', 'Logo (light variant, for dark backgrounds)', 'nullable|string|max:255', true],
        'logo_dark' => ['branding', 'image', 'Logo (dark variant, for light backgrounds)', 'nullable|string|max:255', true],
        'favicon' => ['branding', 'image', 'Favicon (.ico / .png)', 'nullable|string|max:255', true],

        // Contact
        'contact_phone' => ['contact', 'text', 'Primary phone', 'nullable|string|max:50', true],
        'contact_phone_secondary' => ['contact', 'text', 'Secondary phone', 'nullable|string|max:50', true],
        'contact_email' => ['contact', 'email', 'Primary email', 'nullable|email|max:150', true],
        'contact_email_secondary' => ['contact', 'email', 'Secondary email', 'nullable|email|max:150', true],
        'contact_address' => ['contact', 'textarea', 'Address', 'nullable|string|max:500', true],
        'office_hours' => ['contact', 'text', 'Office hours', 'nullable|string|max:120', true],
        'enquiry_notify_email' => ['contact', 'email', 'Send enquiry notifications to (private)', 'nullable|email|max:150', false],

        // Social
        'social_facebook' => ['social', 'url', 'Facebook URL', 'nullable|url:https|max:255', true],
        'social_x' => ['social', 'url', 'X (Twitter) URL', 'nullable|url:https|max:255', true],
        'social_linkedin' => ['social', 'url', 'LinkedIn URL', 'nullable|url:https|max:255', true],
        'social_youtube' => ['social', 'url', 'YouTube URL', 'nullable|url:https|max:255', true],
        'social_instagram' => ['social', 'url', 'Instagram URL', 'nullable|url:https|max:255', true],

        // Header
        'topbar_enabled' => ['header', 'boolean', 'Show utility top bar', 'boolean', true],
        'topbar_text' => ['header', 'text', 'Utility bar text', 'nullable|string|max:150', true],
        'header_cta_label' => ['header', 'text', 'Header button label', 'nullable|string|max:40', true],
        'header_cta_url' => ['header', 'text', 'Header button link', 'nullable|string|max:255', true],

        // Footer
        'footer_about' => ['footer', 'textarea', 'Footer about text', 'nullable|string|max:500', true],
        'copyright_text' => ['footer', 'text', 'Copyright text', 'nullable|string|max:200', true],
        'newsletter_title' => ['footer', 'text', 'Newsletter title', 'nullable|string|max:100', true],
        'newsletter_text' => ['footer', 'text', 'Newsletter text', 'nullable|string|max:250', true],

        // Default call-to-action band
        'cta_title' => ['cta', 'text', 'Default CTA title', 'nullable|string|max:150', true],
        'cta_subtitle' => ['cta', 'text', 'Default CTA subtitle', 'nullable|string|max:250', true],
        'cta_button_label' => ['cta', 'text', 'Default CTA button label', 'nullable|string|max:40', true],
        'cta_button_url' => ['cta', 'text', 'Default CTA button link', 'nullable|string|max:255', true],
        'cta_background' => ['cta', 'image', 'Default CTA background', 'nullable|string|max:255', true],

        // SEO defaults
        'seo_title' => ['seo', 'text', 'Default meta title', 'nullable|string|max:120', true],
        'seo_title_separator' => ['seo', 'text', 'Title separator', 'nullable|string|max:5', true],
        'seo_description' => ['seo', 'textarea', 'Default meta description', 'nullable|string|max:300', true],
        'seo_keywords' => ['seo', 'text', 'Default meta keywords', 'nullable|string|max:255', true],
        'seo_og_image' => ['seo', 'image', 'Default social share image', 'nullable|string|max:255', true],
        'google_site_verification' => ['seo', 'text', 'Google site verification code', 'nullable|alpha_dash|max:100', true],

        // Map
        'map_embed_url' => ['map', 'map', 'Map embed URL (Google Maps "Embed a map" src or OpenStreetMap embed URL)', 'nullable|url:https|max:1000', true],
        'map_link_url' => ['map', 'url', 'Map link (opens in a new tab)', 'nullable|url:https|max:500', true],
    ],

    // Hosts allowed for the map iframe. The admin only ever supplies a URL, never HTML.
    'map_hosts' => ['www.google.com', 'maps.google.com', 'www.openstreetmap.org'],
];
