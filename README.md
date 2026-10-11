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

**Admin panel** (`/admin`):

- Dashboard
- **Homepage builder** and **page builder** (add/edit/hide/reorder approved section types)
- Pages (create unlimited CMS pages), hero slides, menus and sub-menus (header + two footer menus)
- Products and categories (gallery, specifications, featured, draft/published), services, clients
- Team & founder, news and news categories (draft/published/scheduled), history milestones
- Certifications (only *verified* ones are shown publicly), offices, FAQs
- Contact enquiries (status, notes, CSV export) and newsletter subscribers (CSV export)
- Media library (secure uploads)
- General settings: site name, header/footer/light/dark logos, favicon, contact details, social links, utility bar, header button, footer text, copyright, CTA defaults, SEO defaults, map
- Admin users, roles and permissions; personal profile/password

Everything on the public site comes from MySQL. Changing a setting, menu, page, or section is visible immediately and needs no code changes.

---

## Security model (summary)

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

- **Pages** (`pages`) have a *type*: `sections` (content page), `products`, `news` or `contact`. Built-in pages (home, products, news, contact) can't be deleted or change type.
- **Sections** (`page_sections` + `section_items`) form the page builder. Only types defined in [`backend/config/sections.php`](backend/config/sections.php) can be created. JSON is used only for the small, validated per-section `settings`. Repeatable content such as cards, stats and steps is stored as normalized rows in `section_items`, and data-driven sections (services, products, news, clients, founder, milestones, certifications, FAQs, hero slides) read from their own tables.
- **Module placement:** on Products/News/Contact pages, a *Module content* section marks where the catalog/news list/contact form appears.
- **Settings:** key/value rows defined in [`backend/config/cms.php`](backend/config/cms.php). The admin UI renders its forms from these definitions.

Database tables: `users, roles, permissions, permission_role, settings, media, pages, page_sections, section_items, menu_items, hero_slides, product_categories, products, product_images, product_specifications, services, clients, news_categories, news_posts, team_members, milestones, certifications, offices, faqs, contact_submissions, newsletter_subscribers` (plus Laravel's sessions/cache/jobs tables). Indexes cover status/sort/visibility lookups and unique slugs.

---

## Before going live: content to replace or verify

The seeded content reproduces the approved design so the site is complete on day one. **The client must review it:**

- **Images are placeholders.** All seeded images are original vector illustrations generated by `generate_assets.py`, not product photography. Upload real photos in the media library and swap them in, including the founder portrait (currently a neutral silhouette).
- **Facts and contact details** (phone numbers, emails, addresses, the Dhaka office, "15+ years", "500+ clients", history years, founder name/message) are copied from the design mockups. Verify every one.
- **Client logos** are fictional placeholder names. Only add real brand logos with permission.
- **Certifications** (ISO 9001, HACCP, BRCGS, RoHS) are seeded as **not verified** and stay hidden until an admin confirms each certificate and ticks *verified*.
- **Social links** point to the networks' home pages. Set the real profile URLs or clear them.
- The **map** uses a keyless Google Maps embed of "BSCIC Industrial Estate, Konabari". Replace it with the exact location's embed URL.

---

## Project status

**Verified in this workspace:**

- 57 backend tests (237 assertions) pass: authentication, authorization/escalation, CRUD, page builder, public vs private content, validation, uploads, sanitization, settings, forms, newsletter, CORS and headers.
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

**Not verified (please check manually):**

- Real phones and tablets (Safari/iOS, Android browsers). The end-to-end checks used Chrome's mobile emulation.
- The Google Maps iframe loading inside the contact page.
- Sending enquiry notification emails through a real SMTP server.
- Deployment on an actual cPanel host (the documented steps were simulated locally).
- Pages are client-rendered, so search engines that don't run JavaScript see only the default meta tags from `index.html`. Google renders JavaScript; see *SEO notes* in `CPANEL_DEPLOYMENT.md`.
