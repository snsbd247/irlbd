# IR Limited — Corporate Website & CMS

Corporate website and content management system for **IR Limited**, a plastic packaging manufacturer in Bangladesh.

| Part | Stack | Folder |
|---|---|---|
| REST API + admin auth | Laravel 12 (PHP 8.2+), Sanctum (SPA cookie auth), MySQL/MariaDB | [`backend/`](backend/) |
| Public website + admin panel | React 19, Vite, React Router, TanStack Query, Axios, Tailwind CSS 4, TipTap | [`frontend/`](frontend/) |
| Deployment guide | Shared cPanel hosting, no Node.js server required | [`CPANEL_DEPLOYMENT.md`](CPANEL_DEPLOYMENT.md) |
| API reference | Versioned `/api/v1` endpoints | [`API.md`](API.md) |

The React app is built to static files. In production only Apache + PHP run; no Node.js process is needed.

---

## Features

**Public website** (follows the supplied design): dynamic hero slider, home sections, About pages (Founder Message, Mission, Vision, History timeline, Quality Control), searchable/filterable/paginated product catalog with product detail pages, galleries and specifications, news listing with categories, search, pagination and detail pages, a contact page with a validated form, map, offices and FAQ, newsletter signup, site search, generic CMS pages, SEO metadata, sitemap, responsive layouts, and loading/error/empty states.

Added after the first release:

- **English / বাংলা** language switch (top bar and mobile menu). Content comes translated from the API with English fallback; interface text uses a built-in dictionary.
- **Request a Quote** form (`/request-quote`, pre-filled from a product) with email notification.
- **Product variants** (sizes, capacity, weight, neck size, colour, MOQ), **industries** filter, **compare** up to 4 products side by side, and a downloadable **PDF datasheet** per product.
- **Company brochure** download (footer and products page).
- **Photo gallery** albums with a lightbox, **testimonials**, **management team** and **video** sections.
- **Careers**: job posts and an application form with CV upload (CVs are stored privately, never in the web root).
- **WhatsApp** chat button, **Google Analytics 4 / Meta Pixel** (IDs only), Organization/Product **JSON-LD**.
- **Link previews** for WhatsApp/Facebook/LinkedIn: crawlers get per-page Open Graph tags from `share.php`.

**Admin panel** (`/admin`):

- Dashboard
- **Homepage builder** and **page builder** (add/edit/hide/reorder approved section types)
- Pages (create unlimited CMS pages), hero slides, menus and sub-menus (header + two footer menus)
- Products and categories (gallery, specifications, featured, draft/published), services, clients
- Team & founder, news and news categories (draft/published/scheduled), history milestones
- Certifications (only *verified* ones are shown publicly), offices, FAQs
- Contact enquiries (status, notes, CSV export) and newsletter subscribers (CSV export)
- Quote requests and job applications (status, notes, CSV export, private CV download)
- Gallery albums, testimonials, industries, job posts
- **Newsletter campaigns:** write, preview, send a test, then send to all subscribers in small batches (no cron needed)
- **Bengali translations:** a "বাংলা" card on every content form, in the page/section editors, menus, gallery captions and a Settings tab
- **Security:** two-factor authentication (authenticator app + recovery codes), password reset by email, and an activity log of every change
- Media library (secure uploads)
- General settings: site name, header/footer/light/dark logos, favicon, contact details, social links, utility bar, header button, footer text, copyright, CTA defaults, SEO defaults, map, WhatsApp/analytics, brochure, notification addresses
- Admin users, roles and permissions; personal profile/password

Everything on the public site comes from MySQL. Changing a setting, menu, page, or section is visible immediately and needs no code changes.

---

## Security model (summary)

- **Two-factor & recovery:** optional TOTP 2FA per admin (secret and recovery codes encrypted at rest, codes can't be replayed), super admins can reset another user's 2FA, and password-reset links expire after 60 minutes. Reset requests always give the same answer, so they don't reveal which emails exist.
- **Activity log:** create/update/delete on every content model and settings change is recorded with user, IP and changed field names (values of passwords/secrets are never logged).
- **Applicant CVs** are checked by file signature (PDF/DOC/DOCX), stored under `storage/app/private/cvs` with random names, and can only be downloaded by users with the *careers* permission.
- **Auth:** Laravel Sanctum *SPA authentication*: an HttpOnly, Secure, SameSite session cookie plus a CSRF token (`XSRF-TOKEN` cookie → `X-XSRF-TOKEN` header). No token is ever stored in `localStorage`. There is no public registration endpoint, and admins are created by a super admin, the seeder, or `php artisan cms:create-admin`.
- **Authorization:** roles → permissions (`{module}.manage`) enforced by Laravel **Policies** on every admin endpoint. Super admins bypass the checks. Users can't escalate themselves to super admin, delete or deactivate themselves, or edit super admins unless they are one.
- **Validation:** Form Requests on every write. Image fields must reference files in the media library, and links must be relative, `http(s)`, `mailto:` or `tel:` (`javascript:`/`data:` are rejected). Icons and section types come from allow-lists, and section settings are validated per type.
- **No arbitrary HTML/JS:** rich text is sanitized server-side with HTMLPurifier (strict allow-list: no scripts, iframes, styles or event handlers) and again client-side with DOMPurify. The map setting accepts only a Google Maps/OpenStreetMap embed URL, never iframe code.
- **Uploads:** type is detected from content, not the filename. JPEG/PNG/WebP are decoded and re-encoded with GD, which strips metadata and payloads, and downscaled to 2000px. Files get random names, SVG uploads are refused, size is limited (5 MB by default), and `.htaccess` disables script execution in `uploads/`.
- **Abuse protection:** rate limits on login (5/min per email+IP), public forms (5/min, 50/day per IP), uploads and the API. Honeypot fields on public forms, newsletter responses that don't reveal whether an email exists, and CSV exports escaped against formula injection.
- **Privacy:** enquiries, subscribers and private settings are only reachable through authenticated admin endpoints. JSON errors never include stack traces (`APP_DEBUG=false`), and admin API responses send `Cache-Control: no-store`.
- **CORS** is restricted to configured origins with credentials. `.env` and the application code sit outside `public_html`.

---

## Local development

Requirements: PHP 8.2+ (with `pdo_mysql`, `gd`, `fileinfo`, `mbstring`), Composer 2, Node 20+, and MySQL 5.7+/MariaDB 10.4+. SQLite also works for quick tries.

```bash
# 1. Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
# edit .env: APP_ENV=local, APP_DEBUG=true, APP_URL=http://127.0.0.1:8000, DB_* values,
#            FRONTEND_URL=http://localhost:5175, CORS_ALLOWED_ORIGINS=http://localhost:5175,
#            SANCTUM_STATEFUL_DOMAINS=localhost:5175, SESSION_DOMAIN=null,
#            SESSION_SECURE_COOKIE=false, UPLOADS_URL=http://localhost:5175/uploads,
#            ADMIN_EMAIL / ADMIN_PASSWORD (first admin)
php artisan migrate --seed
php artisan serve            # http://127.0.0.1:8000

# 2. Frontend (new terminal)
cd frontend
npm install
npm run dev                  # http://localhost:5175  (proxies /api, /sanctum, /uploads to :8000)
```

Then open <http://localhost:5175> for the website and <http://localhost:5175/admin> to sign in with your `ADMIN_EMAIL`/`ADMIN_PASSWORD`.

> The dev port is **5175** (`strictPort`) so it doesn't collide with other Vite projects on 5173.

### Useful commands

| Command | Purpose |
|---|---|
| `php artisan test` | Backend test suite (SQLite in-memory) |
| `php artisan cms:create-admin` | Create or reset a super admin interactively |
| `php artisan migrate:fresh --seed` | Rebuild the database with demo content (**destroys data**) |
| `npm run build` | Production build into `frontend/dist/` |
| `npx oxlint src` | Frontend lint |
| `bash scripts/build-release.sh https://api.example.com` | Build an upload-ready release in `release/` |
| `python backend/database/seeders/assets/generate_assets.py` | Regenerate the placeholder illustrations |

---

## Content model

- **Pages** (`pages`) have a *type*: `sections` (content page), `products`, `news`, `contact`, `gallery` or `careers`. Built-in pages (home, products, news, contact) can't be deleted or change type.
- **Sections** (`page_sections` + `section_items`) form the page builder. Only types defined in [`backend/config/sections.php`](backend/config/sections.php) can be created. JSON is used only for the small, validated per-section `settings`. Repeatable content such as cards, stats and steps is stored as normalized rows in `section_items`, and data-driven sections (services, products, news, clients, founder, milestones, certifications, FAQs, hero slides) read from their own tables.
- **Module placement:** on Products/News/Contact pages, a *Module content* section marks where the catalog/news list/contact form appears.
- **Settings:** key/value rows defined in [`backend/config/cms.php`](backend/config/cms.php). The admin UI renders its forms from these definitions.
- **Translations:** English lives in the normal columns. Other languages (currently `bn`) are rows in a polymorphic `translations` table (`model, id, locale, field, value`). Public requests with `?lang=bn` get translated fields, and empty ones fall back to English. The admin always edits English plus an explicit `translations: { bn: {...} }` object. `BanglaContentSeeder` adds starter Bengali menu/page/category names (it never overwrites edits); **have a native speaker review all Bengali text before launch.**

Database tables: `users, roles, permissions, permission_role, settings, media, pages, page_sections, section_items, menu_items, hero_slides, product_categories, products, product_images, product_specifications, product_variants, industries, industry_product, services, clients, testimonials, gallery_albums, gallery_images, news_categories, news_posts, team_members, milestones, certifications, offices, faqs, contact_submissions, quote_requests, job_posts, job_applications, newsletter_subscribers, newsletter_campaigns, newsletter_deliveries, activity_logs, translations` (plus Laravel's sessions/cache/jobs tables). Indexes cover status/sort/visibility lookups and unique slugs.

---

## Before going live: content to replace or verify

The seeded content reproduces the approved design so the site is complete on day one. **The client must review it:**

- **Images are placeholders.** All seeded images are original vector illustrations generated by `generate_assets.py`, not product photography. Upload real photos in the media library and swap them in, including the founder portrait (currently a neutral silhouette).
- **Facts and contact details** (phone numbers, emails, addresses, the Dhaka office, "15+ years", "500+ clients", history years, founder name/message) are copied from the design mockups. Verify every one.
- **Client logos** are fictional placeholder names. Only add real brand logos with permission.
- **Certifications** (ISO 9001, HACCP, BRCGS, RoHS) are seeded as **not verified** and stay hidden until an admin confirms each certificate and ticks *verified*.
- **Social links** point to the networks' home pages. Set the real profile URLs or clear them.
- The **map** uses a keyless Google Maps embed of "BSCIC Industrial Estate, Konabari". Replace it with the exact location's embed URL.
- **Logo:** the supplied logo reads "The Container **Marker**". This is probably meant to be "Maker"; confirm with the client.
- **Bengali text** (menus, interface, page names) was written without a native-speaker review. Check it, and add Bengali versions of the main page content in the admin.
- **Social previews:** seeded images are SVG placeholders, which Facebook/WhatsApp don't show. Set a JPG/PNG *Social share image* (Settings → SEO, or per page).
- **WhatsApp number, GA4 / Pixel IDs and the brochure PDF** are empty until set in Settings. The WhatsApp button stays hidden until a number is entered.

---

## Project status

**Verified in this workspace:**

- 107 backend tests (571 assertions) pass: authentication, 2FA, password reset, activity log, authorization/escalation, CRUD, page builder, public vs private content, validation, uploads (images, PDFs, CVs), sanitization, settings, contact/quote/careers forms and their emails, newsletter campaigns and batching, translations, link-preview meta, CORS and headers.
- Migrations and seeders run on MariaDB 10.4.
- The production frontend build succeeds; the admin panel and editor are code-split from the public bundle.
- The admin login → authenticated request → CSRF-protected write → upload flow was exercised over HTTP. A write without the CSRF header is rejected (419).
- The packaged release (`scripts/build-release.sh`) was booted with PHP's built-in server and installed through the token-protected web installer against an empty database.
- Desktop screenshots of the home, History, Quality Control, Products and Contact pages were compared with the design.
- A browser end-to-end run in headless Chrome passed all 89 checks:
  - **Phone and tablet (390px and 820px):** 12 public pages with no horizontal overflow, plus the mobile menu and submenu.
  - **Public flows:** contact form validation and submission, "Request a Quote" pre-fill, newsletter signup, hero slider, header search, catalog filter and search, news category tabs, breadcrumbs and 404.
  - **Admin:** login with wrong and correct passwords, all 29 admin screens loading without errors, and product create/validate/delete including specifications and rich text.
  - **Admin editing:** category reordering, the homepage builder (edit, repeater add/remove, hide/show, add/delete section), creating and deleting a CMS page, settings that update the live site, map-URL rejection, the favicon via the media picker, media upload, menu add/delete, enquiry status, CSV export and logout.

- Each later feature step was also checked in headless Chrome. The checks covered the quote form, gallery and lightbox, careers application with CV upload/download, variants/industry filter/compare, 2FA enrolment and login, password reset, activity log, newsletter preview/test/batch sending (emails written to the log mailer), WhatsApp/analytics tags, crawler link previews through Apache with `.htaccess`, and the English ↔ বাংলা switch on public pages plus Bengali editing in the admin.

**Not verified (please check manually):**

- Real phones and tablets (Safari/iOS, Android browsers). The end-to-end checks used Chrome's mobile emulation.
- The Google Maps iframe loading inside the contact page.
- Sending emails (notifications, password reset, newsletters) through a real SMTP server. Locally, emails were only checked with Laravel's log mailer and test fakes.
- Link previews as rendered by the real Facebook/WhatsApp crawlers (only simulated with their user agents).
- The wording of the Bengali translations.
- Deployment on an actual cPanel host (the documented steps were simulated locally).
- Pages are client-rendered. Google renders JavaScript, and social crawlers get per-page tags from `share.php`, but other non-JavaScript crawlers only see the defaults from `index.html`. See *SEO notes* in `CPANEL_DEPLOYMENT.md`.
