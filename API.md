# IR Limited API — v1

Base URL: `https://api.example.com/api/v1` (development: `http://localhost:5175/api/v1` through the Vite proxy).

- All responses are JSON. Errors use `{ "message": "..." }`, and validation errors (422) add `{ "errors": { "field": ["..."] } }`. Stack traces are never returned when `APP_DEBUG=false`.
- Lists are paginated Laravel-style: `{ "data": [...], "links": {...}, "meta": { "current_page", "last_page", "per_page", "total", "from", "to" } }`.
- Single resources are returned **unwrapped**, except `products/{slug}` and `news/{slug}`, which return `{ "data": {...}, "related"|"recent": [...] }`.
- Image fields come in pairs: `image` (media-library path, used when saving) and `image_url` (absolute public URL).

Status codes: `200` OK · `201` created · `204` deleted · `401` not signed in · `403` no permission / inactive account · `404` not found · `405` not allowed · `419` CSRF token missing/expired · `422` validation · `429` rate limited.

---

## Authentication (admin)

The admin panel uses **Laravel Sanctum SPA cookie authentication**. No API tokens are issued.

1. `GET /sanctum/csrf-cookie` (on the API host, outside `/api/v1`) sets the `XSRF-TOKEN` cookie.
2. Every `POST/PUT/DELETE` sends that value in the `X-XSRF-TOKEN` header (Axios does this automatically with `withCredentials` + `withXSRFToken`).
3. `POST /admin/login` starts an HttpOnly session cookie. All admin requests must send cookies (`credentials: 'include'`).

Requests only receive a session when they come from a host listed in `SANCTUM_STATEFUL_DOMAINS` (via `Origin`/`Referer`). Public forms from the website therefore also need the CSRF cookie, which the frontend fetches automatically.

| Method | Endpoint | Body | Notes |
|---|---|---|---|
| POST | `/admin/login` | `email`, `password`, `remember?` | Rate limited 5/min per email+IP. Returns `{ user }`. |
| POST | `/admin/logout` | – | Invalidates the session. |
| GET | `/admin/me` | – | `{ user: { id, name, email, role, is_super, permissions[] } }` |
| PUT | `/admin/profile` | `name`, `email`, `current_password?`, `password?`, `password_confirmation?` | Changing the password requires the current one. |

There is **no registration endpoint**.

---

## Public endpoints

Only published/active content is returned. Rate limit: 180 requests/min per IP.

**Language:** add `?lang=bn` to any public GET to receive Bengali text where a translation exists (English otherwise). The response carries `Content-Language`. Unknown languages are ignored.

| Method | Endpoint | Query / body | Returns |
|---|---|---|---|
| GET | `/settings` | – | Public settings (`site_name`, `logo_header_url`, `contact_phone`, `social_*`, `seo_*`, `map_embed_url`, …). Private settings are never included. |
| GET | `/navigation` | – | `{ header: [...], footer_quick: [...], footer_products: [...] }`; items have `label`, `href`, `open_in_new_tab`, `children` |
| GET | `/home` | – | Homepage with active `sections` (each with `items` and, for data-driven types, `data`) |
| GET | `/pages/{slug}` | – | Any published page (same shape as `/home`) |
| GET | `/product-categories` | – | Active categories with `products_count` |
| GET | `/products` | `q`, `category` (slug), `industry` (slug), `featured`, `per_page` (≤48), `page` | Paginated products |
| GET | `/products/compare` | `slugs` (comma separated, ≤4) | Products with specifications, variants and industries |
| GET | `/products/{slug}` | – | `{ data: product (with images, specifications, variants, industries, category, datasheet_url), related: [...] }` |
| GET | `/industries` | – | Active industries that have published products |
| GET | `/services` · `/services/{slug}` | – | Active services |
| GET | `/news` | `q`, `category` (slug), `per_page` (≤30), `page` | Paginated published posts (no full content) |
| GET | `/news/recent` | – | 3 latest posts |
| GET | `/news/{slug}` | – | `{ data: post (with content), recent: [...] }` |
| GET | `/news-categories` | – | Categories with published `posts_count` |
| GET | `/contact-info` | – | `{ offices, faqs, subjects }` |
| GET | `/gallery` · `/gallery/{slug}` | – | Active albums (cover, photo count) · one album with `images[{url, caption}]` |
| GET | `/jobs` · `/jobs/{slug}` | – | Published job posts (`is_open` is false after the deadline) |
| GET | `/meta` | `path` (site path) | `{ title, description, image, type, … }` for link previews; used by `share.php` |
| GET | `/search` | `q` (2–100 chars) | `{ query, results: [{ type: page\|product\|news, title, excerpt, url, image_url }] }` |
| GET | `/sitemap.xml` | – | XML sitemap of published pages, products, categories, services, news |
| POST | `/contact` | `name`, `email`, `phone?`, `subject` (one of `subjects`), `message` (10–5000) | `201 { message }`. Rate limited 5/min, 50/day per IP. Honeypot field `website` must be empty. |
| POST | `/quote` | `name`, `company?`, `email`, `phone`, `product_id?`, `quantity`, `specification?`, `needed_by?` (date), `message?` | `201 { message }`. Same limits and honeypot as `/contact`; emails the enquiry address. |
| POST | `/jobs/{slug}/apply` | multipart: `name`, `email`, `phone`, `cover_letter?`, `cv` (PDF/DOC/DOCX ≤ `UPLOAD_CV_MAX_KB`), `consent` (accepted) | `201 { message }`. Rejected with 422 when the job is closed. CV stored privately. |
| POST | `/newsletter/subscribe` | `email` | `201 { message }`, the same response whether or not the address already exists |
| POST | `/newsletter/unsubscribe` | `token` | `{ message }` |

### Section types

`GET /home` and `GET /pages/{slug}` return `sections[]`. Each section has `type`, `eyebrow`, `title`, `subtitle`, `content` (sanitized HTML), `image_url`, `video_url`, `button_label`, `button_url`, `settings` (object), `items[]` and, where noted, `data`:

| Type | `data` source | Notable settings |
|---|---|---|
| `hero_slider` | active hero slides | `autoplay`, `interval` |
| `feature_strip`, `stats`, `process_steps`, `icon_cards` | – (uses `items`) | `icon_cards.style`: strip/cards/split, `columns` |
| `about_intro` | – | `badge_value`, `badge_label` |
| `image_text` | – | `image_position`, `item_style` (side_cards/inline/checklist/icons_row), `card_*`, `background` |
| `services_grid` | services | `limit` |
| `product_categories` | categories | `layout`: cards/banners |
| `featured_products` | featured products | `limit` |
| `clients` | clients | – |
| `latest_news` | latest posts | `limit` |
| `founder_message` | founder (team member) | – |
| `timeline` | milestones | – |
| `certifications` | **verified** certifications only | – |
| `faq` | FAQs | – |
| `module_content` | – | Marks where the product catalog / news list / contact form renders |
| `rich_text`, `cta_banner`, `newsletter` | – | – |

The authoritative definitions are in [`backend/config/sections.php`](backend/config/sections.php).

---

## Admin endpoints

**Login with 2FA:** `POST /admin/login` returns `{ two_factor: true }` instead of the user when the account has 2FA. Then `POST /admin/two-factor-challenge` with `code` (6 digits) or `recovery_code` completes the login (same session; 5 attempts/min).

**Password reset:** `POST /admin/forgot-password` `{ email }` always answers 200. The email links to `/admin/reset-password?token=…&email=…`, and the SPA posts `{ token, email, password, password_confirmation }` to `POST /admin/reset-password`.

**Translations:** every translatable resource (pages, sections and items, menus, products and their specifications/variants, categories, news, services, team, testimonials, gallery, jobs, FAQs, offices, milestones, certifications, hero slides, clients, industries) returns `translations: { bn: { field: value } }` in admin responses and accepts the same object on create/update. Empty values delete a translation, unknown fields/locales are ignored, and rich text is sanitized.

All require a session + active account and are authorized by policies. Each module requires `{module}.manage` (super admins have everything). Admin responses carry `Cache-Control: no-store`.

### Generic CRUD resources

For each resource below:

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/admin/{resource}` | `search`, `page`, `per_page` (≤100), `sort` + `direction`, resource filters, or `all=1` for an unpaginated list (≤500) |
| POST | `/admin/{resource}` | Create (validated) → `201` |
| GET | `/admin/{resource}/{id}` | Show |
| PUT | `/admin/{resource}/{id}` | Update |
| DELETE | `/admin/{resource}/{id}` | `204` |
| POST | `/admin/{resource}/reorder` | `{ ids: [3, 1, 2] }` sets `sort_order` in that order |

| Resource | Permission | Filters | Key fields |
|---|---|---|---|
| `pages` | pages | `status`, `template` | `title`, `slug`, `template` (sections/products/news/contact/gallery/careers), `hero_*`, `content`, `status`, `is_home`, `published_at`, `meta_*`, `og_image`. Built-in pages can't be deleted or change template. |
| `hero-slides` | pages | `is_active` | `eyebrow`, `title`, `highlight`, `title_suffix`, `subtitle`, `image`, `primary_*`, `secondary_*`, `is_active` |
| `menu-items` | menus | `location`, `is_active` | `location` (header/footer_quick/footer_products), `parent_id` (two levels), `label`, `link_type` (page/url), `page_id`, `url`, `open_in_new_tab`, `is_active` |
| `product-categories` | products | `is_active` | `name`, `slug`, `icon`, `short_description`, `description`, `image`, `is_active`, `meta_*` |
| `products` | products | `status`, `category`, `is_featured` | `name`, `slug`, `sku`, `product_category_id`, `short_description`, `description`, `image`, `images[{path,alt}]`, `specifications[{label,value,translations?}]`, `variants[{name,capacity,weight,neck_size,color,moq,translations?}]`, `industry_ids[]`, `datasheet` (PDF path), `is_featured`, `status`, `meta_*` |
| `industries` | products | `is_active` | `name`, `slug`, `icon`, `is_active` |
| `testimonials` | clients | `is_active` | `name`, `designation`, `company`, `quote`, `photo`, `rating` (1–5), `is_active` |
| `gallery-albums` | gallery | `is_active` | `title`, `slug`, `description`, `cover_image`, `event_date`, `images[{path, alt, alt_bn?}]` (≤200), `is_active` |
| `job-posts` | careers | `status` | `title`, `slug`, `department`, `location`, `employment_type`, `vacancies`, `salary`, `deadline`, `summary`, `description`, `status`, `published_at`, `meta_*` |
| `job-applications` | careers | `status`, `job` (job post id) | read-only except `status` and `admin_note`; `POST` returns 405 |
| `quote-requests` | enquiries | `status` | read-only except `status` and `admin_note`; `POST` returns 405 |
| `newsletter-campaigns` | subscribers | `status` | `subject`, `preheader`, `content` (rich text). Editable only as a draft. |
| `services` | services | `is_active` | `title`, `slug`, `icon`, `short_description`, `description`, `image`, `is_active` |
| `clients` | clients | `is_active` | `name`, `logo`, `website_url`, `is_active` |
| `team-members` | team | `is_active`, `is_founder` | `name`, `designation`, `photo`, `signature`, `message`, `quote`, `is_founder`, `is_active` |
| `news-categories` | news | – | `name`, `slug`, `is_active` |
| `news-posts` | news | `status`, `category`, `is_featured` | `title`, `slug`, `news_category_id`, `excerpt`, `content`, `featured_image`, `status`, `is_featured`, `published_at`, `meta_*` |
| `milestones` | history | `is_active` | `year`, `title`, `description`, `image`, `is_active` |
| `certifications` | quality | `is_active`, `is_verified` | `name`, `logo`, `description`, `is_verified`, `is_active` |
| `offices` | contact | `is_active` | `name`, `address`, `phone`, `email`, `image`, `is_active` |
| `faqs` | contact | `is_active` | `question`, `answer`, `is_active` |
| `enquiries` | enquiries | `status` | read-only except `status` (new/read/replied/archived) and `admin_note`. Opening one marks it read; `POST` returns 405. |
| `subscribers` | subscribers | `status` | `status` (subscribed/unsubscribed) only; `POST` returns 405 |
| `users` | users | `role`, `is_active` | `name`, `email`, `password` (required on create; ≥10 chars, letters+numbers), `role_id`, `is_active` |
| `roles` | users | – | `name`, `description`, `permissions[]` (slugs) |

Validation rules common to all resources:

- Image fields must be an existing media-library `path`.
- Links (`*_url`, `button_url`, `link_url`, menu `url`) must be relative (`/…`, `#…`), `http(s)://`, `mailto:` or `tel:`.
- Icons must come from the allow-list in [`config/cms.php`](backend/config/cms.php).
- Rich text is sanitized on save.

### Page builder

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/admin/pages/{page}/sections` | All sections (including hidden) with items |
| POST | `/admin/pages/{page}/sections` | `type` (approved types only) + fields; appended at the end |
| POST | `/admin/pages/{page}/sections/reorder` | `{ ids: [...] }` |
| GET / PUT / DELETE | `/admin/sections/{section}` | `PUT` accepts section fields, `settings` (unknown keys dropped, values validated per type) and `items[]`. Items with an `id` are updated, items without one are created, and missing ones are deleted. Order follows the array. |

### Other admin endpoints

| Method | Endpoint | Permission | Notes |
|---|---|---|---|
| GET | `/admin/dashboard` | any | Stats limited to modules the user can access |
| GET | `/admin/meta` | any | Section type definitions, icon list, settings schema, permissions, templates |
| GET / PUT | `/admin/settings` | settings | `{ values, image_urls, translations }`; PUT accepts any keys from the settings schema plus `translations: { bn: { key: value } }` for the keys in `translatable_settings`. The map URL must be a Google Maps or OpenStreetMap embed URL. |
| POST | `/admin/sitemap` | settings | Writes `sitemap.xml` to `SITEMAP_OUTPUT_PATH` |
| GET | `/admin/media` | media or any content permission | `folder`, `search`, `page`, `per_page` |
| POST | `/admin/media` | media or any content permission | multipart `file` (JPG/PNG/WebP/GIF/ICO ≤ `UPLOAD_MAX_KB`, or PDF ≤ `UPLOAD_DOCUMENT_MAX_KB`), `folder?`, `alt?` → `201` media. Rate limited 60/min. |
| PUT | `/admin/media/{id}` | media | `alt` |
| DELETE | `/admin/media/{id}` | media | Deletes the record and the file |
| GET | `/admin/enquiries/export` | enquiries | CSV download |
| GET | `/admin/quote-requests/export` | enquiries | CSV download |
| GET | `/admin/job-applications/{id}/cv` | careers | Downloads the applicant's CV (private file) |
| GET | `/admin/newsletter-campaigns/recipients` | subscribers | `{ subscribed, batch_size }` |
| GET | `/admin/newsletter-campaigns/{id}/preview` | subscribers | `{ html }` of the email |
| POST | `/admin/newsletter-campaigns/{id}/test` | subscribers | `{ email }` sends one test copy (5/min) |
| POST | `/admin/newsletter-campaigns/{id}/send-batch` | subscribers | Sends the next `NEWSLETTER_BATCH_SIZE` emails and returns `{ sent, failed, remaining, done, campaign }`. The admin page calls it repeatedly until `done`, and a cache lock prevents parallel batches (`busy: true`). |
| POST / DELETE | `/admin/two-factor` | own account | Start enrolment (`password` → `{ secret, qr_svg }`) / turn off (`password`) |
| POST | `/admin/two-factor/confirm` | own account | `code` → `{ recovery_codes }` |
| POST | `/admin/two-factor/recovery-codes` | own account | `password` → new recovery codes |
| DELETE | `/admin/users/{id}/two-factor` | users | Reset another user's 2FA |
| GET | `/admin/activity-log` | users | `user`, `action`, `type`, `search`, `page` |
| GET | `/admin/subscribers/export` | subscribers | CSV download |

### Installer (no-SSH hosts)

`POST /api/install` with `token=<INSTALL_TOKEN>`. Returns 404 unless `INSTALL_TOKEN` (≥32 chars) is set. The first run migrates and seeds; later runs only migrate. Rate limited 5/min. See [`CPANEL_DEPLOYMENT.md`](CPANEL_DEPLOYMENT.md).
