# Deploying IR Limited on shared cPanel hosting

This guide assumes **nothing** beyond a standard cPanel account: no SSH, no Composer, no Node.js, no cron, no symlinks. Steps that need SSH have a no-SSH alternative. Everything is built on your own computer and uploaded.

```
Your PC (build)                         cPanel account (/home/USER)
────────────────                        ──────────────────────────────────────────────
release/frontend/      ── upload ──▶    public_html/                 → https://example.com
release/api-public/    ── upload ──▶    api.example.com/  (doc root) → https://api.example.com
release/irlbd-api/     ── upload ──▶    irlbd-api/   (NOT web-accessible: code, .env, storage, vendor)
```

Replace `example.com` with the real domain and `USER` with the cPanel username throughout.

---

## Quick reference: irlbd.com (API in the `/api` folder)

IR Limited uses **one domain, no subdomain**: the website is `https://irlbd.com` and the API lives in the folder `https://irlbd.com/api`. This is *Option B* of step 2. Follow the guide with these exact values:

```
/home/USER/
├── public_html/              ← contents of release/frontend  (website)
│   └── api/                  ← contents of release/api-public (index.php, .htaccess, uploads/)
└── irlbd-api/                ← release/irlbd-api (code, .env, storage, vendor — NOT web-accessible)
```

| What | Value |
|---|---|
| Build command (step 1) | `bash scripts/build-release.sh https://irlbd.com/api` |
| API endpoints | `https://irlbd.com/api/api/v1/...`. The folder is `/api` and Laravel adds its own `/api` prefix. Visitors never see these URLs. |
| Web installer (step 7, no SSH) | `curl -X POST https://irlbd.com/api/api/install -d "token=YOUR_INSTALL_TOKEN"` |
| `index.php` | No edit needed: it finds `/home/USER/irlbd-api` automatically. |

`.env` values (step 5) that differ from the generic example:

```dotenv
APP_URL=https://irlbd.com/api
FRONTEND_URL=https://irlbd.com
CORS_ALLOWED_ORIGINS=https://irlbd.com,https://www.irlbd.com
SANCTUM_STATEFUL_DOMAINS=irlbd.com,www.irlbd.com
SESSION_DOMAIN=.irlbd.com
SESSION_SECURE_COOKIE=true
UPLOADS_ROOT=/home/USER/public_html/api/uploads
UPLOADS_URL=https://irlbd.com/api/uploads
SITEMAP_OUTPUT_PATH=/home/USER/public_html
```

Recommended: send `www.irlbd.com` to `irlbd.com` (cPanel → **Domains → Redirects**, or *Force HTTPS Redirect* plus a www redirect) so every visitor uses the same address. `www` also works without this, thanks to the CORS and cookie settings above.

When you **update** the site later (step 12), replace the files in `public_html/` **but keep the `public_html/api/` folder** (especially `api/uploads/`), and only replace `api/index.php` and `api/.htaccess` inside it.

This exact layout was tested locally under Apache with the release package: web installer, pages, Bengali switch, contact/quote/job-application forms (CV upload and admin download), admin login, media upload, uploaded images and sitemap writing.

---

## 0. Check the hosting first

In cPanel:

1. **MultiPHP Manager / Select PHP Version** → set the domain(s) to **PHP 8.2 or newer**.
2. **Select PHP Version → Extensions**: make sure `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `tokenizer`, `xml`, `ctype`, `bcmath`, `curl` and `zip` are enabled. `gd` and `fileinfo` are required for secure image uploads.
3. **Domains**: confirm you can create a subdomain and choose its document root. Nearly every cPanel host allows this. If yours doesn't, use **Option B** in step 2.
4. `mod_rewrite` must be available. It almost always is on cPanel; the site will not route without it.

If you can't see these screens, ask the host to confirm PHP ≥ 8.2 with the extensions above.

---

## 1. Build the release on your computer

Requirements on your PC: PHP 8.2+, Composer, Node 20+, Git Bash (Windows) or any bash shell.

```bash
bash scripts/build-release.sh https://api.example.com
```

This creates `release/` and `release/irlbd-release.zip`:

| Folder | What it is | Goes to |
|---|---|---|
| `frontend/` | Static React build (`index.html`, `assets/`, `.htaccess`, `robots.txt`, `share.php` + `share-config.php` for link previews) | `public_html/` |
| `api-public/` | Laravel's public folder; its `index.php` boots the app from `../irlbd-api` | API subdomain document root |
| `irlbd-api/` | Laravel app with production `vendor/` already installed | `/home/USER/irlbd-api` (outside `public_html`) |

> The API URL is baked into the frontend at build time. If it changes, rebuild and re-upload `frontend/`.

---

## 2. Create the API subdomain

**Option A (recommended): subdomain `api.example.com`**

cPanel → **Domains → Create a New Domain** (or *Subdomains* in older cPanel):

- Domain: `api.example.com`
- Document root: `/home/USER/api.example.com`. Untick "Share document root with example.com".

Folder layout after upload:

```
/home/USER/
├── public_html/            ← frontend files
├── api.example.com/        ← contents of release/api-public (index.php, .htaccess, uploads/)
└── irlbd-api/              ← contents of release/irlbd-api (app, vendor, storage, .env)
```

`api-public/index.php` looks for the app in `../irlbd-api` and `../../irlbd-api`, which is `/home/USER/irlbd-api` with this layout. If you put the folders elsewhere, edit `$app_path` at the top of `index.php`.

**Option B: no subdomain (API in a subfolder of the main site; used for irlbd.com)**

- Upload `release/api-public/*` (including `.htaccess`) to `public_html/api/`.
- Upload `release/irlbd-api/` to `/home/USER/irlbd-api` (still outside `public_html`). `index.php` finds it automatically.
- Build the frontend with `bash scripts/build-release.sh https://example.com/api`. API URLs become `https://example.com/api/api/v1/...`.
- In `.env` use `APP_URL=https://example.com/api`, `UPLOADS_ROOT=/home/USER/public_html/api/uploads`, `UPLOADS_URL=https://example.com/api/uploads`. See the *Quick reference* at the top for the full list.

The rest of this guide uses Option A.

---

## 3. Create the MySQL database

cPanel → **MySQL® Database Wizard**:

1. Database: e.g. `USER_irlbd`
2. User: e.g. `USER_irlbd` with a long random password
3. Privileges: **ALL PRIVILEGES** on that database

Write down the full database name, user and password (cPanel prefixes them with `USER_`).

---

## 4. Upload the files

Using **File Manager** (enable *Settings → Show Hidden Files* so `.htaccess` and `.env` are visible), or FTP/SFTP:

1. Upload `irlbd-release.zip` to `/home/USER/` and **Extract**.
2. Move the contents of `frontend/` into `public_html/`, including `.htaccess`.
3. Move the contents of `api-public/` into `api.example.com/`, including `.htaccess` and `uploads/.htaccess`.
4. Rename/keep `irlbd-api/` at `/home/USER/irlbd-api`.
5. Delete the leftover empty folders and the zip.

---

## 5. Configure `.env`

Copy `/home/USER/irlbd-api/.env.example` to `/home/USER/irlbd-api/.env` and edit it:

```dotenv
APP_NAME="IR Limited"
APP_ENV=production
APP_KEY=base64:REPLACE_ME          # see below
APP_DEBUG=false                     # never true in production
APP_URL=https://api.example.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=USER_irlbd
DB_USERNAME=USER_irlbd
DB_PASSWORD=the-database-password

FRONTEND_URL=https://example.com
CORS_ALLOWED_ORIGINS=https://example.com,https://www.example.com
SANCTUM_STATEFUL_DOMAINS=example.com,www.example.com
SESSION_DOMAIN=.example.com
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync

# Uploads live in the API web root. Always set these in production.
UPLOADS_ROOT=/home/USER/api.example.com/uploads
UPLOADS_URL=https://api.example.com/uploads

# Lets "Regenerate sitemap" write public_html/sitemap.xml
SITEMAP_OUTPUT_PATH=/home/USER/public_html

# First super admin (delete ADMIN_PASSWORD after installing!)
ADMIN_NAME="Site Administrator"
ADMIN_EMAIL=you@example.com
ADMIN_PASSWORD=a-long-unique-password-123

# Only for the no-SSH installer in step 7 (delete afterwards)
INSTALL_TOKEN=
```

### Generating `APP_KEY` without SSH

Run one of these **on your PC** and paste the output (including `base64:`) into `APP_KEY`:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```
```powershell
"base64:" + [Convert]::ToBase64String((1..32 | % { [byte](Get-Random -Max 256) }))
```

Keep `APP_KEY` safe and never change it after launch: it encrypts sessions.

### Cookie & CORS settings explained

| Setting | Why |
|---|---|
| `SANCTUM_STATEFUL_DOMAINS` | Requests from these hosts get session cookies (admin login + public forms' CSRF). List every host visitors use (`example.com` and `www.example.com`). |
| `SESSION_DOMAIN=.example.com` | Lets the cookie set by `api.example.com` be read on `example.com`. Must be the shared parent domain with a leading dot. |
| `CORS_ALLOWED_ORIGINS` | Exact origins (scheme + host) allowed to call the API with credentials. Use `https://`. |
| `SESSION_SECURE_COOKIE=true` | Cookies only over HTTPS. Requires SSL on both domains (step 8). |

---

## 6. File permissions

In File Manager → select → **Change Permissions**:

| Path | Permission |
|---|---|
| `irlbd-api/storage` and everything inside | folders `755`, files `644` (use `775`/`664` only if your host runs PHP as a different user and writes fail) |
| `irlbd-api/bootstrap/cache` | `755` |
| `api.example.com/uploads` | `755` |
| `irlbd-api/.env` | `640` (or `600`) |
| Everything else | folders `755`, files `644` |

Never use `777`.

---

## 7. Install: migrations, seed data and the first admin

### Option A: with SSH or cPanel "Terminal"

```bash
cd ~/irlbd-api
php artisan migrate --force
php artisan db:seed --force          # roles, settings, pages, menus, demo content, first admin
php artisan cms:create-admin         # optional: create/reset another super admin interactively
php artisan config:cache
php artisan route:cache
```

`UPLOADS_ROOT` must be set (step 5) so the seeder copies images into the API web root.

If your host's default `php` is older than 8.2, use the versioned binary, e.g. `/opt/cpanel/ea-php82/root/usr/bin/php artisan …`.

### Option B: without SSH (one-time web installer)

1. Put a random value of **at least 32 characters** in `.env`: `INSTALL_TOKEN=...`
   (generate one with `php -r "echo bin2hex(random_bytes(24));"` or a password manager).
2. From your PC run (Windows 10/11 and macOS include `curl`):

   ```bash
   curl -X POST https://api.example.com/api/install -d "token=YOUR_INSTALL_TOKEN"
   ```
   PowerShell:
   ```powershell
   Invoke-RestMethod -Method Post -Uri https://api.example.com/api/install -Body @{ token = "YOUR_INSTALL_TOKEN" }
   ```
3. The response lists the migration and seeding output. On the first run it migrates and seeds (creating the admin from `ADMIN_EMAIL`/`ADMIN_PASSWORD`), then writes `storage/app/private/installed.lock` so seeding never runs again. Later runs only apply new migrations.
4. **Immediately remove `INSTALL_TOKEN` and `ADMIN_PASSWORD` from `.env`.** Without the token the endpoint returns 404.

Seeding happens only when the database has no pages, so it never overwrites edited content. To start without demo products/news/clients, set `SEED_DEMO_CONTENT=false` before installing.

---

## 8. SSL (HTTPS)

1. cPanel → **SSL/TLS Status** → run **AutoSSL** for `example.com`, `www.example.com` and `api.example.com`.
2. Once both certificates are active, uncomment the HTTPS redirect in `public_html/.htaccess`:
   ```apache
   RewriteCond %{HTTPS} !=on
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```
3. Use `https://` everywhere in `.env` and rebuild the frontend if `VITE_API_URL` was `http://`.

---

## 9. Verify the deployment

| Check | Expected |
|---|---|
| `https://api.example.com/` | `{"name":"IR Limited API","version":"v1",...}` |
| `https://api.example.com/api/v1/settings` | JSON with `site_name` |
| `https://api.example.com/api/v1/pages/does-not-exist` | `{"message":"Resource not found."}` (no stack trace) |
| `https://example.com/` | Homepage with hero slider |
| `https://example.com/our-mission`, then refresh | Page reloads (not a 404): the SPA `.htaccess` works |
| `https://example.com/admin` | Login screen; sign in with the admin you created |
| Admin → Settings → change the site name → save | New name on the public site after a reload |
| Contact form | "Message sent", and the enquiry appears under Admin → Enquiries |
| Admin → Media → upload a JPG | Thumbnail appears; file is under `api.example.com/uploads/general/…` |

---

## 10. After launch

- **Change the admin password** (Admin → My profile) if `ADMIN_PASSWORD` was ever shared.
- Replace the placeholder images and verify the seeded facts (see README → *Before going live*).
- **Sitemap:** Admin → General settings → SEO → *Regenerate sitemap.xml* (needs `SITEMAP_OUTPUT_PATH`). Then check that `Sitemap:` in `public_html/robots.txt` uses your domain (the build fills it in automatically when the API is a folder of the site, as for irlbd.com). Regenerate after publishing major content. A live sitemap is always available at `https://api.example.com/api/v1/sitemap.xml`.
- **Email (strongly recommended):** either create a mailbox in cPanel → *Email Accounts* and fill the SMTP `MAIL_*` values in `.env`, or send through the server's own mail system with `MAIL_MAILER=sendmail` and `MAIL_SENDMAIL_PATH="/usr/sbin/sendmail -t -i"` (no mailbox needed for `no-reply@`; irlbd.com uses this). Check that the domain's SPF and DKIM are valid in cPanel → *Email Deliverability*. Email is needed for the *Forgot password* link, enquiry/quote/job-application notifications (set the addresses in *Admin → Settings → Contact*) and newsletters. Submissions are always stored even if email fails. Send yourself a test from *Admin → Newsletter → Send test* to confirm.
- **Newsletters:** sending runs in batches of `NEWSLETTER_BATCH_SIZE` (default 20) while the admin keeps the *Send* page open, so no cron is needed. Many shared hosts cap outgoing email (often 100–500 per hour). Ask your host and keep the list size in mind. If a send is interrupted, open the campaign again and press *Resume sending*.
- **Two-factor authentication:** each admin can turn it on in *My profile*. Store the recovery codes safely. If someone loses their phone, a super admin can reset their 2FA from *Users*.
- **Link previews:** paste a page URL into the [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/) to check the title/image. Set a JPG/PNG *Social share image* first, because SVG images aren't shown by Facebook/WhatsApp.
- **CV retention:** job applications and CVs older than *Settings → Contact → Delete job applications and CVs older than* (default 365 days) are deleted by `php artisan cms:prune-applications` (cron above).
- **Admin IP allow-list (optional):** set `ADMIN_ALLOWED_IPS=203.0.113.10,203.0.113.0/24` in `.env` (then `php artisan config:cache`) to allow the admin panel only from your office network. Check the IP first at https://ifconfig.me, or you will lock yourself out (remove the line to undo).
- **Dealers page:** add dealers in *Admin → Dealers*, then publish the *Dealers & Distributors* page (it starts as a draft so the site never shows an empty list).
- **Bengali:** the website has an EN | বাংলা switch. Add Bengali versions of the content in each form's *বাংলা* card and in *Settings → বাংলা*. Empty fields show English.
- **Cron is not required.** Queues run synchronously and expired sessions are cleaned up automatically during normal traffic.

---

## 11. Backups

### Automatic daily backup (cron)

`irlbd-api/scripts/backup.sh` dumps the website database (credentials read from `.env`, never placed on the command line) and archives the uploads and applicant CVs into `~/irlbd-backups/daily/`, keeping 14 days. It only touches this website. Add it in cPanel → **Cron Jobs** (or `crontab -e`):

```
# IR Limited website: backup at 02:30, delete old job applications/CVs at 03:00
30 2 * * * /bin/bash $HOME/irlbd-api/scripts/backup.sh >> $HOME/irlbd-backups/backup.log 2>&1
0 3 * * * cd $HOME/irlbd-api && php artisan cms:prune-applications >> $HOME/irlbd-backups/backup.log 2>&1
```

Backups on the same server don't protect against losing the server: download a copy from `~/irlbd-backups/daily/` now and then (or use the host's off-site backup). To restore a dump: `gunzip -c db-YYYY-MM-DD.sql.gz | mysql -u USER -p DATABASE`.

### Manual backups

| What | How | How often |
|---|---|---|
| Database | cPanel → **Backup** → *Download a MySQL Database Backup*, or phpMyAdmin → Export (SQL) | Daily/weekly, and before every update |
| Uploaded media | Download `api.example.com/uploads/` (File Manager → Compress) | Weekly |
| Applicant CVs | Download `irlbd-api/storage/app/private/cvs/` (personal data: store it securely and delete old CVs per your retention policy) | Weekly |
| Configuration | Download `irlbd-api/.env` and keep it somewhere private (it contains secrets) | After every change |
| Full account | cPanel → **Backup** → *Full Backup*, or JetBackup if the host provides it | Monthly |

To restore: import the SQL in phpMyAdmin, re-upload `uploads/` and `storage/app/private/cvs/`, restore `.env`.

---

## 12. Updating and rolling back

**Update:**

1. Back up the database, `uploads/` and `.env` (section 11).
2. Build a new release on your PC.
3. Upload the new `irlbd-api/` as `/home/USER/irlbd-api-new`, copy your `.env` into it, and copy the whole `storage/app/private/` folder (`installed.lock` and applicants' `cvs/`).
4. Rename `irlbd-api` → `irlbd-api-previous`, then `irlbd-api-new` → `irlbd-api`.
5. Replace `api.example.com/index.php` and `.htaccess` if they changed. **Keep `uploads/`.**
6. Replace the files in `public_html/` with the new `frontend/` (keep `sitemap.xml`). The new build includes its own `share-config.php`.
7. Apply migrations: SSH `php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache`, **or** temporarily set `INSTALL_TOKEN` and call `/api/install` (migrate-only after the first install), then remove the token.

**Roll back:** rename `irlbd-api-previous` back to `irlbd-api`, restore the previous frontend files and, if the update ran migrations, import the database backup taken in step 1.

---

## 13. Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| **500 error** on the API | Read `irlbd-api/storage/logs/laravel-*.log`. Usual causes: wrong PHP version, missing extension, wrong DB credentials, `storage/` not writable, empty `APP_KEY`, wrong `$app_path` in `index.php`. |
| Blank page or "No application encryption key" | `APP_KEY` missing or malformed; it must start with `base64:`. |
| **404 when refreshing** `/our-mission` | `public_html/.htaccess` is missing (hidden files weren't uploaded) or `mod_rewrite` is off. |
| **CORS error** in the browser console | `CORS_ALLOWED_ORIGINS` must exactly match the site origin, including `https://` and `www.` if used. Run `php artisan config:clear` if config was cached. |
| **419 "Page expired"** / CSRF mismatch | `SESSION_DOMAIN` must be `.example.com`; `SANCTUM_STATEFUL_DOMAINS` must list the site host(s); both sites must use HTTPS when `SESSION_SECURE_COOKIE=true`. Clear browser cookies and retry. |
| Login succeeds then immediately logs out / 401 | Same cookie settings as above; check the browser isn't blocking third-party cookies for a *different* domain (API and site must share a parent domain). |
| Changes to `.env` have no effect | Config is cached: `php artisan config:clear` (SSH), or call the installer with a token (it clears caches). |
| Uploaded images return 404 | `UPLOADS_ROOT` must be the real path of `api.example.com/uploads`, and `UPLOADS_URL` its public URL. |
| "The image could not be processed" | PHP `gd` extension is disabled; enable it in *Select PHP Version*. |
| Mixed-content warnings | `APP_URL`, `UPLOADS_URL` and `VITE_API_URL` must all be `https://`. |
| Too many attempts (429) | Rate limit hit (login 5/min, forms 5/min). Wait a minute. |
| Installer returns 404 | `INSTALL_TOKEN` is empty or shorter than 32 characters (this is the safe default). |
| Whole site shows **500** right after uploading `public_html` | An Apache module used in `.htaccess` is missing. The file needs `mod_rewrite` and permission to set `Options -Indexes`; everything else is optional. Check `public_html/error_log` and ask the host to enable them. |
| Link previews show no image/title | `share-config.php` is missing or has the wrong `api_url` (rebuild with the correct `API_URL`), or the share image is an SVG. |
| "Forgot password" email never arrives | `MAIL_*` settings are wrong or the host blocks SMTP; check `storage/logs/laravel-*.log`. A super admin can still set a new password for the user. |
| CV upload fails with "too large" | Raise `upload_max_filesize` / `post_max_size` in *Select PHP Version → Options* to at least `UPLOAD_CV_MAX_KB` (default 5 MB) and PDF limits (15 MB). |
| Newsletter stops after some emails | The host's hourly email limit was reached. Wait, then press *Resume sending*. Already-sent addresses are skipped. |

---

## SEO notes

The site is a client-rendered single-page app. Google renders JavaScript and picks up the per-page titles, descriptions and Open Graph tags that the app sets.

**Social link previews:** WhatsApp, Facebook, LinkedIn, X, Telegram, Slack and similar crawlers don't run JavaScript. `public_html/.htaccess` recognises them by user agent and sends only them to `share.php`, which fetches `/api/v1/meta?path=…` and returns a tiny page with the right title, description and image. Visitors and Google always get the normal app. `share.php` reads the API address from `share-config.php`, which the release script writes from `API_URL`.

Other crawlers that don't execute JavaScript only see the defaults in `index.html`. A prerendering step could be added later if needed.
