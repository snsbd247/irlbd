#!/usr/bin/env bash
# Builds an upload-ready release for shared cPanel hosting (no Node/Composer needed on the server).
#
#   Usage: [PHP_HANDLER=ea-php82] scripts/build-release.sh https://api.example.com
#
# Produces:
#   release/frontend/          -> upload contents to public_html/
#   release/api-public/        -> upload contents to the API subdomain's document root
#   release/irlbd-api/         -> upload to /home/USER/irlbd-api (OUTSIDE public_html)
#   release/irlbd-release.zip  -> all of the above in one archive
set -euo pipefail

API_URL="${1:-}"
if [[ -z "$API_URL" ]]; then
  echo "Usage: $0 https://api.example.com" >&2
  exit 1
fi
if [[ ! "$API_URL" =~ ^https?://[A-Za-z0-9.:/_-]+$ ]]; then
  echo "Invalid API URL: $API_URL (expected e.g. https://api.example.com)" >&2
  exit 1
fi
API_URL="${API_URL%/}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/release"
rm -rf "$OUT"
mkdir -p "$OUT"

echo "==> Building React frontend (VITE_API_URL=$API_URL)"
( cd "$ROOT/frontend" && { [ -d node_modules ] || npm ci; } && VITE_API_URL="$API_URL" npm run build )
cp -r "$ROOT/frontend/dist" "$OUT/frontend"
# API address for share.php (social-media link previews).
cat > "$OUT/frontend/share-config.php" <<CONF
<?php
return ['api_url' => '$API_URL'];
CONF
# (Overwrites the local-development share-config.php copied from public/.)
# When the API is a folder of the website (https://example.com/api), the website
# address is known: point robots.txt's Sitemap line at it.
if [[ "$API_URL" =~ ^(https?://[^/]+)/.+ ]]; then
  SITE_ORIGIN="${BASH_REMATCH[1]}"
  sed -i "s#^Sitemap: .*#Sitemap: $SITE_ORIGIN/sitemap.xml#" "$OUT/frontend/robots.txt"
  sed -i '/^# Replace example.com/,/^# Admin > Settings > SEO/d' "$OUT/frontend/robots.txt"
  # Limit the website's response headers to its own host (not sub-folder sites).
  SITE_HOST="${SITE_ORIGIN#*://}"; SITE_HOST="${SITE_HOST#www.}"; SITE_HOST="${SITE_HOST%%:*}"
  HOST_RE="^(www[.])?$(printf '%s' "$SITE_HOST" | sed 's/[.]/[.]/g')(:[0-9]+)?\$"
  sed -i "s|^SetEnvIfNoCase Host \".\*\" IR_SITE|SetEnvIfNoCase Host \"$HOST_RE\" IR_SITE|" "$OUT/frontend/.htaccess"
fi
# cPanel writes its PHP-version handler into public_html/.htaccess; uploading a new
# .htaccess removes it. PHP_HANDLER=ea-php82 (as shown in cPanel > MultiPHP Manager)
# puts the same block back so share.php keeps running on the chosen PHP version.
if [[ -n "${PHP_HANDLER:-}" ]]; then
  if [[ ! "$PHP_HANDLER" =~ ^[a-z0-9-]+$ ]]; then
    echo "Invalid PHP_HANDLER: $PHP_HANDLER (expected e.g. ea-php82)" >&2
    exit 1
  fi
  cat >> "$OUT/frontend/.htaccess" <<HANDLER

# php -- BEGIN cPanel-generated handler, do not edit
# Set the "$PHP_HANDLER" package as the default "PHP" programming language.
<IfModule mime_module>
  AddHandler application/x-httpd-$PHP_HANDLER .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit
HANDLER
fi

echo "==> Preparing Laravel API (production dependencies only)"
mkdir -p "$OUT/irlbd-api"
( cd "$ROOT/backend" && tar --exclude=./vendor --exclude=./node_modules --exclude=./.env --exclude='./storage/logs/*' \
    --exclude='./storage/framework/sessions/*' --exclude='./storage/framework/cache/data/*' --exclude='./storage/framework/views/*' --exclude='./storage/framework/testing' \
    --exclude='./public/uploads' --exclude='./database/database.sqlite' \
    --exclude='./tests' --exclude='./.phpunit.result.cache' -cf - . ) | ( cd "$OUT/irlbd-api" && tar -xf - )
( cd "$OUT/irlbd-api" && composer install --no-dev --optimize-autoloader --no-interaction --no-progress )

echo "==> Splitting public directory for the API document root"
mv "$OUT/irlbd-api/public" "$OUT/api-public"
mkdir -p "$OUT/api-public/uploads"
cp "$ROOT/backend/public/uploads/.htaccess" "$OUT/api-public/uploads/.htaccess"
# index.php that boots the app from the folder OUTSIDE the web root.
cat > "$OUT/api-public/index.php" <<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Path to the Laravel application, which lives OUTSIDE the public web root.
// Found automatically for /home/USER/api.example.com (subdomain) and
// /home/USER/public_html/api (subfolder). Otherwise set the path here.
$app_path = null;
foreach ([dirname(__DIR__).'/irlbd-api', dirname(__DIR__, 2).'/irlbd-api', getenv('HOME').'/irlbd-api'] as $candidate) {
    if (is_file($candidate.'/bootstrap/app.php')) {
        $app_path = $candidate;
        break;
    }
}
if ($app_path === null) {
    http_response_code(500);
    exit('Application folder "irlbd-api" not found. Set $app_path in index.php.');
}

if (file_exists($maintenance = $app_path.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $app_path.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $app_path.'/bootstrap/app.php';

// This folder is the public directory (uploads live here; no symlinks needed).
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
PHP

cp "$ROOT/backend/.env.example" "$OUT/irlbd-api/.env.example"

echo "==> Zipping"
( cd "$OUT" && (command -v zip >/dev/null && zip -qr irlbd-release.zip frontend api-public irlbd-api || powershell -NoProfile -Command "Compress-Archive -Path frontend,api-public,irlbd-api -DestinationPath irlbd-release.zip -Force") )

echo
echo "Release ready in $OUT"
echo "Next: follow CPANEL_DEPLOYMENT.md"
