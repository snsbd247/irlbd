<?php

namespace Database\Seeders;

use App\Models\Certification;
use App\Models\Client;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\Media;
use App\Models\MenuItem;
use App\Models\Milestone;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use App\Models\Office;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\TeamMember;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Initial website content reproducing the approved IR Limited design.
 * Every record is editable from the admin panel. Contact details, figures and names come
 * from the supplied design mockups and must be verified by the client before launch.
 * Images are original placeholder illustrations: replace them with real photography.
 */
class ContentSeeder extends Seeder
{
    protected array $media = [];

    public function run(): void
    {
        $this->importAssets();
        $this->settings();
        $this->slides();
        $catalog = config('cms.seed_demo_content');
        if ($catalog) {
            $this->catalog();
            $this->news();
            $this->clients();
            $this->certifications();
        }
        $this->services();
        $this->team();
        $this->milestones();
        $this->contactContent();
        $pages = $this->pages();
        $this->menus($pages);
        $this->call([ShowcaseContentSeeder::class, CareersContentSeeder::class]);
    }

    // ------------------------------------------------------------------ assets

    protected function importAssets(): void
    {
        $dir = database_path('seeders/assets');
        foreach ([...glob($dir.'/*.svg'), ...glob($dir.'/*.png')] as $file) {
            $name = basename($file);
            $isPng = str_ends_with($name, '.png');
            $path = 'seed/'.$name;
            Storage::disk('uploads')->put($path, file_get_contents($file));
            $size = $isPng ? @getimagesize($file) : null;
            Media::updateOrCreate(['path' => $path], [
                'original_name' => $name,
                'mime_type' => $isPng ? 'image/png' : 'image/svg+xml',
                'size' => filesize($file),
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
                'folder' => str_starts_with($name, 'ir-') ? 'branding' : 'general',
                'alt' => str_starts_with($name, 'ir-') ? 'IR Limited logo' : ucwords(str_replace(['-', '.svg', '.png'], [' ', '', ''], $name)),
            ]);
            $this->media[pathinfo($name, PATHINFO_FILENAME)] = $path;
        }

        // The official IR Limited logo (ir-*.png) is used for branding; a GD-drawn icon is only a fallback.
        if (isset($this->media['ir-favicon'])) {
            $this->media['favicon'] = $this->media['ir-favicon'];

            return;
        }

        // Generate a PNG favicon with GD (ICO/PNG favicons are the most widely supported).
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(64, 64);
            imagesavealpha($img, true);
            imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
            $blue = imagecolorallocate($img, 22, 119, 229);
            $white = imagecolorallocate($img, 255, 255, 255);
            imagefilledrectangle($img, 4, 4, 59, 59, $blue);
            imagefilledrectangle($img, 16, 16, 23, 47, $white);
            imagefilledrectangle($img, 29, 16, 36, 47, $white);
            imagefilledrectangle($img, 29, 16, 47, 22, $white);
            imagefilledrectangle($img, 41, 16, 47, 32, $white);
            imagefilledrectangle($img, 29, 29, 47, 34, $white);
            imageline($img, 38, 34, 48, 47, $white);
            imageline($img, 39, 34, 49, 47, $white);
            imageline($img, 40, 34, 50, 47, $white);
            ob_start();
            imagepng($img);
            $png = ob_get_clean();
            imagedestroy($img);
            Storage::disk('uploads')->put('seed/favicon.png', $png);
            Media::updateOrCreate(['path' => 'seed/favicon.png'], [
                'original_name' => 'favicon.png', 'mime_type' => 'image/png', 'size' => strlen($png),
                'width' => 64, 'height' => 64, 'folder' => 'branding', 'alt' => 'Favicon',
            ]);
            $this->media['favicon'] = 'seed/favicon.png';
        }
    }

    protected function m(string $name): ?string
    {
        return $this->media[$name] ?? null;
    }

    // ---------------------------------------------------------------- settings

    protected function settings(): void
    {
        app(SettingsService::class)->save([
            'site_name' => 'IR Limited',
            'site_tagline' => 'Manufacturer of Plastic Packaging Product',
            'logo_header' => $this->m('ir-logo'),
            'logo_footer' => $this->m('ir-logo-light'),
            'logo_light' => $this->m('ir-logo-light'),
            'logo_dark' => $this->m('ir-logo'),
            'favicon' => $this->m('favicon'),
            'contact_phone' => '+880 9633917362',
            'contact_phone_secondary' => '+880 2 9876543',
            'contact_email' => 'info@irlbd.com',
            'contact_email_secondary' => 'sales@irlbd.com',
            'contact_address' => 'Plot # A 30-31, A 22-23, BSCIC Industrial Estate, Konabari, Gazipur - 1346, Bangladesh',
            'office_hours' => 'Sun - Thu, 9:00 AM - 6:00 PM',
            'social_facebook' => 'https://www.facebook.com/',
            'social_x' => 'https://x.com/',
            'social_linkedin' => 'https://www.linkedin.com/',
            'topbar_enabled' => true,
            'topbar_text' => 'Quality Packaging for a Sustainable Tomorrow',
            'header_cta_label' => 'Get a Quote',
            'header_cta_url' => '/request-quote',
            'footer_about' => 'A trusted manufacturer of high-quality plastic packaging products, delivering innovative and sustainable solutions for a better tomorrow.',
            'copyright_text' => '© {year} IR Limited. All Rights Reserved.',
            'newsletter_title' => 'Newsletter',
            'newsletter_text' => 'Subscribe to our newsletter for the latest updates, news and product information.',
            'cta_title' => 'Looking for a Reliable Packaging Partner?',
            'cta_subtitle' => "Let's discuss how we can support your business with high-quality plastic packaging solutions.",
            'cta_button_label' => 'Contact Us',
            'cta_button_url' => '/contact',
            'cta_background' => $this->m('production-line'),
            'seo_title' => 'IR Limited | Plastic Packaging Manufacturer in Bangladesh',
            'seo_title_separator' => '|',
            'seo_description' => 'IR Limited manufactures high-quality PET & HDPE bottles, caps, closures, jars and containers in Bangladesh, with a focus on safety, sustainability and customer satisfaction.',
            'seo_keywords' => 'plastic packaging Bangladesh, PET bottles, HDPE bottles, caps and closures, jars, containers, IR Limited',
            'seo_og_image' => $this->m('hero-packaging'),
            'map_embed_url' => 'https://www.google.com/maps?q=BSCIC+Industrial+Estate,+Konabari,+Gazipur&output=embed',
            'map_link_url' => 'https://www.google.com/maps/search/?api=1&query=BSCIC+Industrial+Estate+Konabari+Gazipur',
        ]);
    }

    // ------------------------------------------------------------------ slides

    protected function slides(): void
    {
        HeroSlide::create([
            'eyebrow' => 'Trusted Plastic Packaging Manufacturer',
            'title' => 'Innovative',
            'highlight' => 'Packaging',
            'title_suffix' => 'for a Better Tomorrow',
            'subtitle' => 'We manufacture high-quality plastic packaging products that ensure safety, sustainability, and customer satisfaction across diverse industries.',
            'image' => $this->m('hero-packaging'),
            'primary_label' => 'Explore Our Products',
            'primary_url' => '/products',
            'secondary_label' => 'Contact Us',
            'secondary_url' => '/contact',
        ]);
        HeroSlide::create([
            'eyebrow' => 'Caps, Closures & Containers',
            'title' => 'Precision',
            'highlight' => 'Closures',
            'title_suffix' => 'Built for Every Industry',
            'subtitle' => 'Screw caps, flip-top caps, jars and containers engineered for food, pharma, cosmetic and household brands.',
            'image' => $this->m('hero-closures'),
            'primary_label' => 'View Caps & Closures',
            'primary_url' => '/products?category=plastic-caps-closures',
            'secondary_label' => 'Get a Quote',
            'secondary_url' => '/contact',
        ]);
    }

    // ----------------------------------------------------------------- catalog

    protected function catalog(): void
    {
        $bottles = ProductCategory::create([
            'name' => 'Plastic Bottles', 'slug' => 'plastic-bottles', 'icon' => 'bottle', 'image' => $this->m('bottles'),
            'short_description' => 'Wide range of PET and HDPE bottles for water, juice, soft drinks, pharma, dairy and household products.',
        ]);
        $caps = ProductCategory::create([
            'name' => 'Plastic Caps & Closures', 'slug' => 'plastic-caps-closures', 'icon' => 'cylinder', 'image' => $this->m('caps'),
            'short_description' => 'Screw caps, flip-top caps, child-resistant closures and customized cap solutions.',
        ]);
        $jars = ProductCategory::create([
            'name' => 'Jars & Containers', 'slug' => 'jars-containers', 'icon' => 'container', 'image' => $this->m('jars'),
            'short_description' => 'Food storage jars, cosmetic jars and industrial-grade containers in various sizes and designs.',
        ]);

        $products = [
            [$bottles, 'PET Water Bottle', 'pet-water-bottle', true, 'Crystal-clear, lightweight PET bottles for drinking water and beverages.',
                ['Material' => 'PET (food grade)', 'Capacity' => '250 ml – 2 L', 'Neck finish' => '28 mm PCO 1881', 'Colour' => 'Clear / Blue tint', 'MOQ' => '10,000 pcs']],
            [$bottles, 'HDPE Pharma Bottle', 'hdpe-pharma-bottle', true, 'Opaque HDPE bottles for tablets, capsules and liquid pharmaceuticals.',
                ['Material' => 'HDPE', 'Capacity' => '30 ml – 500 ml', 'Closure' => 'Child-resistant / tamper-evident', 'Colour' => 'White / Custom']],
            [$caps, 'Flip Top Cap', 'flip-top-cap', true, 'Hinged flip-top caps for sauces, shampoos, lotions and personal care products.',
                ['Material' => 'PP', 'Size' => '24 mm – 38 mm', 'Orifice' => 'Custom', 'Colour' => 'Any Pantone']],
            [$caps, 'Screw Cap', 'screw-cap', true, 'Reliable screw caps with tamper-evident bands for bottles and containers.',
                ['Material' => 'HDPE / PP', 'Size' => '28 mm – 63 mm', 'Liner' => 'Optional induction seal']],
            [$jars, 'Cosmetic Jar', 'cosmetic-jar', true, 'Premium jars for creams, gels and cosmetic products.',
                ['Material' => 'PP / PET', 'Capacity' => '15 g – 250 g', 'Finish' => 'Glossy / Matte']],
            [$jars, 'Food Storage Jar', 'food-storage-jar', true, 'Food-grade PET jars for snacks, spices, confectionery and dry foods.',
                ['Material' => 'PET (food grade)', 'Capacity' => '250 ml – 3 L', 'Lid' => 'PP screw lid']],
            [$bottles, 'HDPE Gallon Container', 'hdpe-gallon-container', false, 'Durable HDPE containers for edible oil, chemicals and household liquids.',
                ['Material' => 'HDPE', 'Capacity' => '1 L – 5 L', 'Handle' => 'Integrated']],
            [$bottles, 'PET Preform', 'pet-preform', false, 'High-clarity PET preforms for in-house bottle blowing.',
                ['Material' => 'PET', 'Weight' => '9 g – 48 g', 'Neck finish' => '28 mm / 30 mm / 38 mm']],
        ];

        $images = ['pet-water-bottle', 'hdpe-pharma-bottle', 'flip-top-cap', 'screw-cap', 'cosmetic-jar', 'food-storage-jar', 'hdpe-gallon', 'preform'];

        foreach ($products as $i => [$category, $name, $slug, $featured, $short, $specs]) {
            $product = Product::create([
                'product_category_id' => $category->id,
                'name' => $name,
                'slug' => $slug,
                'sku' => 'IR-'.str_pad((string) ($i + 101), 4, '0', STR_PAD_LEFT),
                'short_description' => $short,
                'description' => '<p>'.$short.' Manufactured in our Gazipur facility using modern injection and blow moulding technology, with strict quality inspection at every stage.</p><h3>Key features</h3><ul><li>Food-grade, certified raw materials</li><li>Consistent wall thickness and dimensional accuracy</li><li>Custom colours, sizes and branding available</li><li>Bulk supply with on-time nationwide delivery</li></ul>',
                'image' => $this->m($images[$i]),
                'is_featured' => $featured,
                'status' => 'published',
            ]);
            $product->images()->create(['path' => $this->m($images[$i]), 'alt' => $name, 'sort_order' => 1]);
            $product->images()->create(['path' => $category->image, 'alt' => $category->name, 'sort_order' => 2]);
            $n = 1;
            foreach ($specs as $label => $value) {
                $product->specifications()->create(['label' => $label, 'value' => $value, 'sort_order' => $n++]);
            }
        }
    }

    // -------------------------------------------------------------------- news

    protected function news(): void
    {
        $cats = [];
        foreach (['Company News', 'Product News', 'Events', 'Press Release', 'Sustainability', 'Quality'] as $name) {
            $cats[$name] = NewsCategory::create(['name' => $name]);
        }

        $posts = [
            ['Company News', 'IR Limited Expands Production Capacity', '2024-01-15', 'factory',
                'IR Limited has successfully commissioned a new production line to meet the growing demand for high-quality plastic packaging products.'],
            ['Product News', 'Launch of New PET Bottle Series', '2023-12-28', 'pet-water-bottle',
                'We are proud to introduce our new range of lightweight and durable PET bottles, designed for beverage and pharmaceutical applications.'],
            ['Events', 'IR Limited Participates in International Trade Fair', '2023-11-10', 'factory-banner',
                'IR Limited showcased its latest packaging solutions at the Dhaka International Trade Fair 2023, receiving an overwhelming response.'],
            ['Sustainability', 'Commitment to a Greener Tomorrow', '2023-10-05', 'sustainability',
                'We are continuously working to reduce our environmental footprint through sustainable raw materials and energy-efficient production.'],
            ['Quality', 'Strengthening Our Quality Control Process', '2023-09-18', 'quality-lab',
                'IR Limited has upgraded its laboratory facilities to ensure stricter quality control and higher product safety standards.'],
            ['Product News', 'New Range of Caps & Closures', '2023-08-25', 'caps',
                'Introducing our latest collection of screw caps, flip-top caps, and child-resistant closures for diverse packaging needs.'],
        ];

        foreach ($posts as [$cat, $title, $date, $image, $excerpt]) {
            NewsPost::create([
                'news_category_id' => $cats[$cat]->id,
                'title' => $title,
                'excerpt' => $excerpt,
                'content' => '<p>'.$excerpt.'</p><p>This milestone reflects our long-term commitment to quality, innovation and customer satisfaction. Our team continues to invest in modern technology, skilled people and responsible manufacturing practices.</p><blockquote>"We remain focused on delivering packaging solutions that create real value for our customers and partners."</blockquote><p>For more information, please contact our sales team.</p>',
                'featured_image' => $this->m($image),
                'status' => 'published',
                'is_featured' => $cat === 'Company News',
                'published_at' => $date.' 09:00:00',
            ]);
        }
    }

    // ------------------------------------------------------------ other content

    protected function services(): void
    {
        foreach ([
            ['Product Design & Development', 'pen-tool', 'Concept development, 3D modeling and prototyping.'],
            ['Plastic Molding Solutions', 'factory', 'Injection molding, blow molding and high-precision manufacturing.'],
            ['Tooling & Engineering', 'wrench', 'Mold design, manufacturing and tool maintenance.'],
            ['Packaging Solutions', 'package', 'Customized bottle manufacturing, food-grade and industrial packaging.'],
            ['Quality Assurance & Testing', 'shield-check', 'Strict quality control, international compliance and continuous monitoring.'],
            ['Logistics & Delivery', 'truck', 'On-time nationwide delivery and efficient distribution network.'],
        ] as [$title, $icon, $short]) {
            Service::create([
                'title' => $title, 'icon' => $icon, 'short_description' => $short,
                'description' => '<p>'.$short.'</p><p>Our experienced team works closely with each customer, from the first brief to final delivery, to make sure every packaging solution meets technical, regulatory and brand requirements.</p>',
            ]);
        }
    }

    protected function clients(): void
    {
        foreach (['AquaPure', 'FreshFarm', 'NovaPharma', 'Crystal Drinks', 'GreenLeaf Foods', 'Bright Home'] as $i => $name) {
            Client::create(['name' => $name, 'logo' => $this->m('client-'.($i + 1))]);
        }
    }

    protected function certifications(): void
    {
        // Seeded as NOT verified: they stay hidden until an admin confirms the certificate is valid.
        foreach ([['ISO 9001:2015', 'iso-9001'], ['HACCP', 'haccp'], ['BRCGS Global Standards', 'brc'], ['RoHS Compliant', 'rohs']] as [$name, $logo]) {
            Certification::create(['name' => $name, 'logo' => $this->m('cert-'.$logo), 'is_verified' => false]);
        }
    }

    protected function team(): void
    {
        TeamMember::create([
            'name' => 'Rizwanul Haq (Reza)',
            'designation' => 'Managing Director',
            'photo' => $this->m('founder-placeholder'),
            'is_founder' => true,
            'quote' => 'Our vision is to create sustainable packaging solutions that add value to our customers and contribute to a better tomorrow.',
            'message' => '<p>It is my pleasure to welcome you to <strong>IR Limited</strong>. Through this platform, I would like to share our journey, our values, and our vision for the future.</p>'
                .'<p>From the very beginning, our focus has been on building a strong foundation in the plastics packaging sector. Over the years, IR Limited has grown with a clear commitment to quality, innovation, and customer satisfaction. By continuously upgrading our facilities, adopting modern technologies, and investing in skilled manpower, we have positioned ourselves as a trusted partner in the industry.</p>'
                .'<p>Our philosophy is simple: <strong>deliver excellence</strong> at every stage. Whether it is product development, manufacturing, or delivery, we aim to provide solutions that not only meet but exceed customer expectations. What makes us strong is our dedicated team—whose passion, professionalism, and hard work have allowed us to overcome challenges and achieve remarkable progress.</p>'
                .'<p>We deeply value the trust our customers have placed in us. Their feedback and cooperation have guided us to improve further and to remain aligned with changing market needs. At IR Limited, we strongly believe that long-term success can only be built on honesty, reliability, and mutual respect.</p>'
                .'<p>Looking ahead, our goal is to expand our capabilities, explore sustainable solutions, and strengthen our relationships with all our stakeholders—customers, partners, and well-wishers alike.</p>'
                .'<p>On behalf of the entire <strong>IR Limited</strong> family, I extend my heartfelt gratitude to all who have supported us in this journey. Together, we look forward to achieving new milestones and creating greater value in the years to come.</p>',
        ]);
    }

    protected function milestones(): void
    {
        foreach ([
            ['2005', 'The Beginning', 'IR Limited was established with a clear vision to manufacturing reliable and high-quality plastic packaging products.', 'factory'],
            ['2010', 'Expanding Production', 'We upgraded our facilities and introduced modern injection molding technology to increase production capacity.', 'production-line'],
            ['2015', 'Product Diversification', 'We expanded our product range to include PET bottles, HDPE bottles, caps, jars and custom packaging solutions.', 'bottles'],
            ['2018', 'New Manufacturing Facility', 'A larger and more advanced manufacturing facility was established to meet the growing demand and ensure higher quality standards.', 'factory-banner'],
            ['2020', 'Strengthening Quality Control', 'We implemented stricter quality control processes and international compliance standards to ensure product safety and reliability.', 'quality-lab'],
            ['2023', 'Towards a Sustainable Future', 'We continue to invest in sustainable solutions, eco-friendly practices, and innovative products to create a cleaner and healthier tomorrow.', 'sustainability'],
        ] as [$year, $title, $description, $image]) {
            Milestone::create(compact('year', 'title', 'description') + ['image' => $this->m($image)]);
        }
    }

    protected function contactContent(): void
    {
        Office::create([
            'name' => 'Head Office & Factory',
            'address' => "Plot # A 30-31, A 22-23\nBSCIC Industrial Estate\nKonabari, Gazipur - 1346\nBangladesh",
            'phone' => '+880 9633917362', 'email' => 'info@irlbd.com', 'image' => $this->m('factory'),
        ]);
        Office::create([
            'name' => 'Dhaka Sales Office',
            'address' => "House # 12, Road # 8\nSector # 1, Uttara\nDhaka - 1230, Bangladesh",
            'phone' => '+880 2 9876543', 'email' => 'sales@irlbd.com', 'image' => $this->m('factory-banner'),
        ]);

        foreach ([
            ['How can I get a product quotation?', 'Send us your requirements through the contact form or email sales@irlbd.com with the product type, size, quantity and any branding needs. Our sales team usually replies within 24 hours.'],
            ['Do you supply internationally?', 'Yes. Alongside nationwide delivery across Bangladesh, we supply selected products to international customers. Contact our sales team for export terms.'],
            ['Can I visit your factory?', 'Yes, factory visits can be arranged by appointment. Please contact us in advance so we can schedule a guided visit.'],
            ['What types of plastic packaging products do you manufacture?', 'We manufacture PET and HDPE bottles, caps and closures, jars, containers and custom packaging for food, beverage, pharmaceutical, cosmetic and industrial use.'],
        ] as [$question, $answer]) {
            Faq::create(compact('question', 'answer'));
        }
    }

    // ------------------------------------------------------------------- pages

    protected function page(array $attributes, array $sections): Page
    {
        $page = Page::create($attributes + ['status' => 'published', 'template' => 'sections']);
        $page->forceFill(['is_system' => $attributes['is_system'] ?? false])->save();

        foreach ($sections as $i => $section) {
            $items = $section['items'] ?? [];
            unset($section['items']);
            $model = $page->sections()->create($section + ['sort_order' => $i + 1, 'is_active' => $section['is_active'] ?? true]);
            foreach ($items as $j => $item) {
                $model->items()->create($item + ['sort_order' => $j + 1]);
            }
        }

        return $page;
    }

    protected function cta(string $title, string $subtitle, string $label = 'Contact Us', string $url = '/contact', ?string $eyebrow = null, ?string $image = null): array
    {
        return ['type' => 'cta_banner', 'eyebrow' => $eyebrow, 'title' => $title, 'subtitle' => $subtitle,
            'button_label' => $label, 'button_url' => $url, 'image' => $image ? $this->m($image) : null];
    }

    protected function pages(): array
    {
        $p = [];

        $p['home'] = $this->page([
            'title' => 'Home', 'slug' => 'home', 'is_home' => true, 'is_system' => true,
            'meta_title' => 'IR Limited | Plastic Packaging Manufacturer in Bangladesh',
        ], [
            ['type' => 'hero_slider', 'admin_label' => 'Hero slider', 'settings' => ['autoplay' => true, 'interval' => 6]],
            ['type' => 'feature_strip', 'items' => [
                ['icon' => 'boxes', 'title' => 'High Quality', 'subtitle' => 'Raw Materials'],
                ['icon' => 'factory', 'title' => 'Modern', 'subtitle' => 'Manufacturing'],
                ['icon' => 'badge-check', 'title' => 'Custom Solutions', 'subtitle' => 'For Your Brand'],
                ['icon' => 'truck', 'title' => 'On-time Delivery', 'subtitle' => 'Nationwide'],
            ]],
            ['type' => 'about_intro', 'eyebrow' => 'About Us', 'title' => 'Committed to Quality Plastic Packaging Solutions',
                'content' => '<p>What sets us apart is our uncompromising focus on quality, punctual delivery, and customer satisfaction. Every step of our process is carefully managed to ensure consistency, efficiency, and reliability.</p><p>With a strong presence in Bangladesh and beyond, <strong>IR Limited</strong> continues to expand its capacity and reach—always striving to deliver packaging solutions that create value and build trust.</p>',
                'image' => $this->m('factory'), 'image_alt' => 'IR Limited factory building',
                'button_label' => 'Learn More', 'button_url' => '/about',
                'settings' => ['badge_value' => '15+', 'badge_label' => 'Years of Excellence'],
                'items' => [
                    ['icon' => 'calendar', 'value' => '15+', 'title' => 'Years Experience'],
                    ['icon' => 'users', 'value' => '500+', 'title' => 'Happy Clients'],
                    ['icon' => 'boxes', 'value' => '1000+', 'title' => 'Product Variations'],
                ]],
            ['type' => 'services_grid', 'eyebrow' => 'Our Services', 'title' => 'Comprehensive Packaging Solutions',
                'subtitle' => 'From concept to delivery, we provide end-to-end plastic packaging solutions to meet diverse industry needs.', 'settings' => ['limit' => 6]],
            ['type' => 'product_categories', 'eyebrow' => 'Our Products', 'title' => 'High-Quality Plastic Packaging Products',
                'subtitle' => 'We offer a wide range of plastic packaging products for different industries.',
                'button_label' => 'View All Products', 'button_url' => '/products', 'settings' => ['layout' => 'cards']],
            ['type' => 'featured_products', 'eyebrow' => 'Featured', 'title' => 'Featured Products', 'button_label' => 'View All Products',
                'button_url' => '/products', 'settings' => ['limit' => 6], 'is_active' => false, 'admin_label' => 'Featured products (hidden by default)'],
            ['type' => 'process_steps', 'eyebrow' => 'How We Work', 'title' => 'Our Manufacturing Process',
                'subtitle' => 'A streamlined process to ensure the highest quality products.', 'settings' => ['background' => 'white'], 'items' => [
                    ['icon' => 'pen-tool', 'title' => 'Design & Concept'],
                    ['icon' => 'layers', 'title' => 'Mold Development'],
                    ['icon' => 'factory', 'title' => 'Manufacturing'],
                    ['icon' => 'shield-check', 'title' => 'Quality Testing'],
                    ['icon' => 'truck', 'title' => 'Packaging & Delivery'],
                ]],
            ['type' => 'clients', 'eyebrow' => 'Our Happy Customers', 'title' => 'Trusted by Leading Brands',
                'subtitle' => 'We are proud to work with renowned brands across various industries.'],
            ['type' => 'latest_news', 'eyebrow' => 'Latest News', 'title' => 'Company News & Updates',
                'subtitle' => 'Stay informed with our latest news, achievements and industry insights.',
                'button_label' => 'View All News', 'button_url' => '/news', 'settings' => ['limit' => 3]],
            $this->cta('Looking for a Reliable Packaging Partner?', "Let's discuss how we can support your business with high-quality plastic packaging solutions.", image: 'production-line'),
        ]);

        $p['about'] = $this->page([
            'title' => 'About Us', 'slug' => 'about', 'hero_title' => 'About IR Limited',
            'hero_subtitle' => 'Quality plastic packaging since 2005', 'hero_image' => $this->m('factory-banner'),
            'meta_description' => 'Learn about IR Limited, a trusted plastic packaging manufacturer in Gazipur, Bangladesh.',
        ], [
            ['type' => 'about_intro', 'eyebrow' => 'Who We Are', 'title' => 'Committed to Quality Plastic Packaging Solutions',
                'content' => '<p>IR Limited is a plastic packaging manufacturer based in the BSCIC Industrial Estate, Konabari, Gazipur. We produce PET and HDPE bottles, caps and closures, jars and containers for food, beverage, pharmaceutical, cosmetic and household brands.</p><p>Our growth is built on modern technology, skilled people and an uncompromising focus on quality and on-time delivery.</p>',
                'image' => $this->m('factory'), 'settings' => ['badge_value' => '15+', 'badge_label' => 'Years of Excellence'],
                'items' => [
                    ['icon' => 'calendar', 'value' => '15+', 'title' => 'Years Experience'],
                    ['icon' => 'users', 'value' => '500+', 'title' => 'Happy Clients'],
                    ['icon' => 'boxes', 'value' => '1000+', 'title' => 'Product Variations'],
                ]],
            ['type' => 'icon_cards', 'eyebrow' => 'Explore', 'title' => 'Get to Know Us', 'settings' => ['style' => 'cards', 'columns' => 5], 'items' => [
                ['icon' => 'users', 'title' => 'Founder Message', 'description' => 'A message from our Managing Director.', 'link_url' => '/founder-message'],
                ['icon' => 'target', 'title' => 'Our Mission', 'description' => 'Creating value through quality and responsibility.', 'link_url' => '/our-mission'],
                ['icon' => 'eye', 'title' => 'Our Vision', 'description' => 'Shaping a cleaner, safer future.', 'link_url' => '/our-vision'],
                ['icon' => 'calendar', 'title' => 'History', 'description' => 'Milestones that shaped IR Limited.', 'link_url' => '/history'],
                ['icon' => 'shield-check', 'title' => 'Quality Control', 'description' => 'Excellence at every stage.', 'link_url' => '/quality-control'],
            ]],
            $this->cta("Let's Build a Sustainable Future Together", 'Partner with IR Limited for innovative and reliable packaging solutions.'),
        ]);

        $values = [
            ['icon' => 'gem', 'title' => 'Quality First', 'description' => 'Delivering consistent and reliable quality.'],
            ['icon' => 'users', 'title' => 'People & Teamwork', 'description' => 'Empowering people to achieve together.'],
            ['icon' => 'leaf', 'title' => 'Sustainable Growth', 'description' => 'Creating value for a better tomorrow.'],
            ['icon' => 'handshake', 'title' => 'Customer Trust', 'description' => 'Building long-term partnerships.'],
        ];

        $p['founder'] = $this->page([
            'title' => 'Founder Message', 'slug' => 'founder-message', 'hero_title' => 'Founder Message',
            'hero_subtitle' => 'A message from our Managing Director', 'hero_image' => $this->m('factory-banner'),
        ], [
            ['type' => 'founder_message', 'eyebrow' => 'Founder Message'],
            ['type' => 'icon_cards', 'settings' => ['style' => 'strip'], 'items' => $values],
            $this->cta("Let's Build a Sustainable Future Together", 'Partner with IR Limited for innovative and reliable packaging solutions.'),
        ]);

        $p['mission'] = $this->page([
            'title' => 'Our Mission', 'slug' => 'our-mission', 'hero_title' => 'Our Mission',
            'hero_subtitle' => 'Creating Value Through Quality, Innovation and Responsibility', 'hero_image' => $this->m('production-line'),
        ], [
            ['type' => 'image_text', 'eyebrow' => 'Our Mission', 'title' => 'Our Mission is to Deliver Packaging Solutions for a Better Tomorrow',
                'content' => '<p>At IR Limited, our mission is to manufacture high-quality plastic packaging products that ensure safety, reliability, and customer satisfaction. We are committed to continuous innovation, sustainable practices, and responsible growth, creating value for our customers, employees, and communities.</p>',
                'image' => $this->m('target'), 'image_alt' => 'Target',
                'settings' => ['image_position' => 'right', 'card_icon' => 'target', 'card_title' => 'Our Goal',
                    'card_text' => 'To be a trusted global partner in plastic packaging solutions through excellence, innovation and sustainability.']],
            ['type' => 'icon_cards', 'settings' => ['style' => 'strip'], 'items' => [
                ['icon' => 'gem', 'title' => 'Quality', 'description' => 'Delivering consistent and reliable quality in every product.'],
                ['icon' => 'lightbulb', 'title' => 'Innovation', 'description' => 'Embracing modern technologies for better solutions.'],
                ['icon' => 'leaf', 'title' => 'Sustainability', 'description' => 'Promoting eco-friendly practices for a cleaner and greener future.'],
                ['icon' => 'users', 'title' => 'People', 'description' => 'Empowering our people and communities to grow together.'],
            ]],
            ['type' => 'image_text', 'eyebrow' => 'Our Commitment', 'title' => 'From Responsibility to Real Impact',
                'content' => '<p>We are committed to reducing environmental impact, optimizing resource usage, and developing sustainable packaging solutions that meet the evolving needs of our customers and the planet.</p>',
                'image' => $this->m('preform'), 'settings' => ['image_position' => 'left', 'item_style' => 'icons_row'], 'items' => [
                    ['icon' => 'recycle', 'title' => 'Eco-Friendly Solutions'],
                    ['icon' => 'leaf', 'title' => 'Efficient Resource Utilization'],
                    ['icon' => 'settings', 'title' => 'Continuous Improvement'],
                    ['icon' => 'bar-chart', 'title' => 'Long-term Value Creation'],
                ]],
            $this->cta('Sustainable Packaging for Future Generations', 'Together, we can create a cleaner, safer and more sustainable world through responsible packaging solutions.', 'Our Vision', '/our-vision', 'A Greener Tomorrow', 'sustainability'),
        ]);

        $p['vision'] = $this->page([
            'title' => 'Our Vision', 'slug' => 'our-vision', 'hero_title' => 'Our Vision',
            'hero_subtitle' => 'Shaping a Cleaner, Safer and More Sustainable Future', 'hero_image' => $this->m('production-line'),
        ], [
            ['type' => 'image_text', 'eyebrow' => 'Our Vision', 'title' => 'To Be a Global Leader in Plastic Packaging Solutions',
                'content' => '<p>Our vision is to be a trusted global partner in plastic packaging solutions, recognized for excellence, innovation, and sustainability. We aim to create long-term value for our customers, employees, partners, and communities by delivering high-quality products that make everyday life safer, more convenient, and more sustainable.</p><p>We envision a future where our innovative packaging solutions contribute to a cleaner environment, a stronger economy, and a better quality of life for generations to come.</p>',
                'image' => $this->m('pet-water-bottle'), 'settings' => ['image_position' => 'right', 'item_style' => 'side_cards'], 'items' => [
                    ['icon' => 'leaf', 'title' => 'A Cleaner Environment', 'description' => 'Innovative solutions for a greener tomorrow.'],
                    ['icon' => 'users', 'title' => 'A Stronger Economy', 'description' => 'Creating value for industries and communities.'],
                    ['icon' => 'globe', 'title' => 'A Better Tomorrow', 'description' => 'Packaging solutions for future generations.'],
                ]],
            ['type' => 'icon_cards', 'eyebrow' => 'Our Focus Areas', 'title' => 'Building a Sustainable Future',
                'subtitle' => 'Our vision is driven by key focus areas that guide our growth and innovation.', 'settings' => ['style' => 'cards', 'columns' => 4], 'items' => [
                    ['icon' => 'leaf', 'title' => 'Sustainable Innovation', 'description' => 'Developing eco-friendly packaging solutions.'],
                    ['icon' => 'settings', 'title' => 'Operational Excellence', 'description' => 'Ensuring consistent quality and efficiency.'],
                    ['icon' => 'users', 'title' => 'People Development', 'description' => 'Empowering our team to achieve greater goals.'],
                    ['icon' => 'bar-chart', 'title' => 'Global Growth', 'description' => 'Expanding our presence in international markets.'],
                ]],
            ['type' => 'image_text', 'eyebrow' => 'Our Commitment', 'title' => 'Making a Positive Impact',
                'content' => '<p>We are committed to continuous improvement, responsible manufacturing, and sustainable practices. Our vision drives us to adopt modern technologies, invest in skilled people, and create innovative products that meet the evolving needs of our customers and the planet.</p>',
                'image' => $this->m('production-line'), 'settings' => ['image_position' => 'left', 'item_style' => 'inline'], 'items' => [
                    ['icon' => 'trophy', 'title' => 'Trusted Partner', 'description' => 'For Global Industries'],
                    ['icon' => 'leaf', 'title' => 'Sustainable', 'description' => 'Solutions'],
                    ['icon' => 'target', 'title' => 'Value for', 'description' => 'Future Generations'],
                ]],
            $this->cta("Let's Build a More Sustainable World", 'Partner with IR Limited for innovative and responsible plastic packaging solutions.', eyebrow: 'Together for a Better Tomorrow', image: 'sustainability'),
        ]);

        $p['history'] = $this->page([
            'title' => 'History', 'slug' => 'history', 'hero_title' => 'Our History',
            'hero_subtitle' => 'A Journey of Growth, Innovation and Trust', 'hero_image' => $this->m('factory-banner'),
        ], [
            ['type' => 'timeline', 'eyebrow' => 'Our Journey', 'title' => 'Milestones That Shaped IR Limited',
                'content' => '<p>Since our inception, IR Limited has been on a continuous journey to deliver high-quality plastic packaging solutions. Our growth is built on innovation, customer trust, and a commitment to excellence. Over the years, we have expanded our capabilities, adopted modern technologies, and created value for industries across Bangladesh and beyond.</p>',
                'subtitle' => 'From a humble beginning to a trusted name in plastic packaging — our history reflects the dedication, innovation, and hard work of our people.'],
            ['type' => 'stats', 'items' => [
                ['icon' => 'calendar', 'value' => '15+', 'title' => 'Years of Excellence'],
                ['icon' => 'factory', 'value' => '500+', 'title' => 'Product Variations'],
                ['icon' => 'users', 'value' => '1000+', 'title' => 'Happy Clients'],
                ['icon' => 'globe', 'value' => 'Exporting', 'title' => 'Across Bangladesh and Beyond'],
            ]],
            $this->cta('Building a Brighter Future Together', 'With a strong foundation and a clear vision, we will continue to innovate, expand, and deliver sustainable packaging solutions for generations to come.', eyebrow: 'Our Commitment Continues', image: 'production-line'),
        ]);

        $p['quality'] = $this->page([
            'title' => 'Quality Control', 'slug' => 'quality-control', 'hero_title' => 'Quality Control',
            'hero_subtitle' => 'Ensuring Excellence at Every Stage', 'hero_image' => $this->m('quality-lab'),
        ], [
            ['type' => 'image_text', 'eyebrow' => 'Our Approach', 'title' => 'Quality is at the Core of Everything We Do',
                'content' => '<p>At IR Limited, quality is not just a process—it is a commitment. We follow strict quality control measures at every stage of our production to ensure that our plastic packaging products meet international standards and exceed customer expectations.</p><p>From raw material inspection to final product testing, we implement advanced technologies, skilled manpower, and well-defined procedures to maintain consistent quality, safety, and reliability.</p>',
                'image' => $this->m('quality-lab'), 'settings' => ['image_position' => 'right', 'item_style' => 'side_cards'], 'items' => [
                    ['icon' => 'gem', 'title' => 'Consistent Quality', 'description' => 'Maintaining high standards in every product.'],
                    ['icon' => 'shield-check', 'title' => 'Safety & Compliance', 'description' => 'Adhering to international standards and regulations.'],
                    ['icon' => 'settings', 'title' => 'Advanced Technology', 'description' => 'Using modern equipment and testing methods.'],
                    ['icon' => 'users', 'title' => 'Trained Professionals', 'description' => 'A skilled team dedicated to quality excellence.'],
                ]],
            ['type' => 'process_steps', 'eyebrow' => 'Our Quality Process', 'title' => 'From Raw Material to Final Product',
                'subtitle' => 'We ensure quality at every stage of production through a systematic and stringent process.', 'settings' => ['background' => 'light'], 'items' => [
                    ['icon' => 'boxes', 'title' => 'Raw Material Inspection'],
                    ['icon' => 'settings', 'title' => 'In-Process Quality Check'],
                    ['icon' => 'flask', 'title' => 'Testing & Analysis'],
                    ['icon' => 'clipboard-check', 'title' => 'Final Product Inspection'],
                    ['icon' => 'package', 'title' => 'Packaging & Dispatch'],
                ]],
            ['type' => 'image_text', 'eyebrow' => 'Our Testing Capabilities', 'title' => 'Advanced Testing for Superior Quality',
                'content' => '<p>Our in-house quality control laboratory is equipped with modern testing instruments to ensure that our products meet the highest industry standards. We conduct various physical, chemical, and performance tests to guarantee durability, safety, and reliability.</p>',
                'image' => $this->m('quality-lab'), 'settings' => ['image_position' => 'left', 'item_style' => 'checklist'], 'items' => [
                    ['title' => 'Dimensional accuracy testing'], ['title' => 'Material composition analysis'],
                    ['title' => 'Leakage and seal integrity testing'], ['title' => 'Heat resistance and durability test'],
                    ['title' => 'Impact and drop test'], ['title' => 'Visual inspection and appearance check'],
                ]],
            ['type' => 'certifications', 'eyebrow' => 'Our Standards', 'title' => 'Compliant with International Standards',
                'subtitle' => 'We follow globally recognized standards to ensure product safety, quality and environmental responsibility.'],
            $this->cta('Delivering Safe, Reliable and Sustainable Packaging', 'Quality Today for a Better Tomorrow.', eyebrow: 'Our Commitment', image: 'production-line'),
        ]);

        $p['products'] = $this->page([
            'title' => 'Our Products', 'slug' => 'products', 'template' => 'products', 'is_system' => true,
            'hero_title' => 'Our Products', 'hero_subtitle' => 'High-Quality Plastic Packaging Solutions for Every Industry', 'hero_image' => $this->m('production-line'),
            'meta_description' => 'PET & HDPE bottles, caps and closures, jars and containers manufactured by IR Limited in Bangladesh.',
        ], [
            ['type' => 'icon_cards', 'eyebrow' => 'Our Product Range', 'title' => 'Innovative Plastic Packaging for a Better Tomorrow',
                'subtitle' => 'We manufacture a wide range of high-quality plastic packaging products designed to meet the diverse needs of the food & beverage, pharmaceutical, cosmetic, and industrial sectors. Our products are made with precision, safety, and sustainability in mind.',
                'settings' => ['style' => 'split'], 'items' => [
                    ['icon' => 'shield-check', 'title' => 'High Quality', 'description' => 'Consistent and reliable quality'],
                    ['icon' => 'settings', 'title' => 'Custom Solutions', 'description' => 'Tailored to meet your needs'],
                    ['icon' => 'leaf', 'title' => 'Safe & Durable', 'description' => 'Food-grade and long-lasting'],
                    ['icon' => 'recycle', 'title' => 'Sustainable', 'description' => 'Environmentally responsible'],
                ]],
            ['type' => 'product_categories', 'settings' => ['layout' => 'banners']],
            ['type' => 'featured_products', 'title' => 'Featured Products', 'settings' => ['limit' => 6]],
            ['type' => 'module_content', 'eyebrow' => 'Catalog', 'title' => 'All Products', 'subtitle' => 'Search and filter our complete product range.'],
            $this->cta('Looking for Custom Packaging Solutions?', 'We are here to help you with the right packaging for your business.', image: 'production-line'),
        ]);

        $p['news'] = $this->page([
            'title' => 'News & Notice', 'slug' => 'news', 'template' => 'news', 'is_system' => true,
            'hero_title' => 'News & Notice', 'hero_subtitle' => 'Latest Updates from IR Limited', 'hero_image' => $this->m('production-line'),
        ], [
            ['type' => 'module_content'],
            $this->cta('Be Part of Our Journey', 'Get the latest news, innovations and updates from IR Limited.', eyebrow: 'Stay Updated', image: 'sustainability'),
        ]);

        $p['contact'] = $this->page([
            'title' => 'Contact Us', 'slug' => 'contact', 'template' => 'contact', 'is_system' => true,
            'hero_title' => 'Contact Us', 'hero_subtitle' => "We're Here to Help You", 'hero_image' => $this->m('factory-banner'),
            'content' => '<p>Have questions about our products or services? We\'d love to hear from you. Get in touch with our team and we\'ll be happy to assist you.</p>',
        ], [
            ['type' => 'module_content'],
            ['type' => 'faq', 'eyebrow' => 'Frequently Asked Questions', 'title' => 'Quick Answers'],
            $this->cta("Let's Build a Stronger Tomorrow Together", 'Partner with IR Limited for innovative and sustainable plastic packaging solutions.', 'Get a Quote', '/contact', image: 'production-line'),
        ]);

        $p['privacy'] = $this->page([
            'title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'hero_title' => 'Privacy Policy',
            'hero_subtitle' => 'How we handle the information you share with us', 'hero_image' => $this->m('factory-banner'),
        ], [
            ['type' => 'rich_text', 'content' => '<h2>Information we collect</h2><p>When you submit the contact form or subscribe to our newsletter we store the details you provide (name, email, phone number and message) so that we can respond to you.</p><h2>How we use it</h2><p>We use this information only to answer your enquiry and, if you subscribed, to send company news. We do not sell your data.</p><h2>Your choices</h2><p>You can ask us to update or delete your information at any time by emailing info@irlbd.com.</p>'],
        ]);

        return $p;
    }

    // ------------------------------------------------------------------- menus

    protected function menus(array $p): void
    {
        $add = fn (string $location, string $label, ?Page $page = null, ?string $url = null, ?int $parent = null) => MenuItem::create([
            'location' => $location, 'label' => $label, 'parent_id' => $parent,
            'link_type' => $page ? 'page' : 'url', 'page_id' => $page?->id, 'url' => $page ? null : $url,
        ]);

        $add('header', 'Home', $p['home']);
        $about = $add('header', 'About', $p['about']);
        $add('header', 'Founder Message', $p['founder'], parent: $about->id);
        $add('header', 'Our Mission', $p['mission'], parent: $about->id);
        $add('header', 'Our Vision', $p['vision'], parent: $about->id);
        $add('header', 'History', $p['history'], parent: $about->id);
        $add('header', 'Quality Control', $p['quality'], parent: $about->id);
        $add('header', 'Our Products', $p['products']);
        $add('header', 'News', $p['news']);
        $add('header', 'Contact Us', $p['contact']);

        $add('footer_quick', 'Home', $p['home']);
        $add('footer_quick', 'About Us', $p['about']);
        $add('footer_quick', 'Our Products', $p['products']);
        $add('footer_quick', 'News', $p['news']);
        $add('footer_quick', 'Contact Us', $p['contact']);
        $add('footer_quick', 'Privacy Policy', $p['privacy']);

        $add('footer_products', 'Plastic Bottles', url: '/products?category=plastic-bottles');
        $add('footer_products', 'Plastic Caps & Closures', url: '/products?category=plastic-caps-closures');
        $add('footer_products', 'Jars & Containers', url: '/products?category=jars-containers');
    }
}
