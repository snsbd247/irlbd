<?php
/**
 * Link previews for social-media crawlers (WhatsApp, Facebook, LinkedIn, X, Telegram...).
 *
 * Those crawlers do not run JavaScript, so .htaccess sends ONLY their requests here.
 * This script asks the API for the page's title/description/image and prints a tiny
 * HTML document with Open Graph tags. Human visitors never reach this file.
 *
 * The API address comes from share-config.php (written by scripts/build-release.sh).
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex');

$config = is_file(__DIR__.'/share-config.php') ? require __DIR__.'/share-config.php' : [];
$api = rtrim((string) ($config['api_url'] ?? ''), '/');

// Only simple site paths are accepted (no hosts, no traversal, no markup).
$path = '/'.trim((string) ($_GET['path'] ?? ''), '/');
if (! preg_match('#^/[A-Za-z0-9/_-]*$#', $path) || strlen($path) > 300) {
    $path = '/';
}

$meta = [];
if ($api !== '' && preg_match('#^https?://#', $api)) {
    $lang = ($_GET['lang'] ?? '') === 'bn' ? 'bn' : null;
    $url = $api.'/api/v1/meta?path='.rawurlencode($path).($lang ? '&lang='.$lang : '');
    $body = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_FOLLOWLOCATION => false]);
        $body = curl_exec($ch);
        curl_close($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $body = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 4]]));
    }
    $decoded = is_string($body) ? json_decode($body, true) : null;
    $meta = is_array($decoded) ? $decoded : [];
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
$pageUrl = $meta['url'] ?? ($scheme.'://'.$host.$path);
$title = $meta['title'] ?? 'IR Limited';
$description = $meta['description'] ?? '';
$image = isset($meta['image']) && preg_match('#^https?://#', (string) $meta['image']) ? $meta['image'] : null;
?><!doctype html>
<html lang="<?= (($_GET['lang'] ?? '') === 'bn') ? 'bn' : 'en' ?>">
<head>
<meta charset="utf-8">
<title><?= $e($title) ?></title>
<meta name="description" content="<?= $e($description) ?>">
<link rel="canonical" href="<?= $e($pageUrl) ?>">
<meta property="og:type" content="<?= $e($meta['type'] ?? 'website') ?>">
<meta property="og:site_name" content="<?= $e($meta['site_name'] ?? 'IR Limited') ?>">
<meta property="og:title" content="<?= $e($title) ?>">
<meta property="og:description" content="<?= $e($description) ?>">
<meta property="og:url" content="<?= $e($pageUrl) ?>">
<?php if ($image) { ?>
<meta property="og:image" content="<?= $e($image) ?>">
<meta name="twitter:image" content="<?= $e($image) ?>">
<?php } ?>
<meta name="twitter:card" content="<?= $image ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= $e($title) ?>">
<meta name="twitter:description" content="<?= $e($description) ?>">
</head>
<body>
<h1><?= $e($title) ?></h1>
<p><?= $e($description) ?></p>
<p><a href="<?= $e($pageUrl) ?>"><?= $e($pageUrl) ?></a></p>
</body>
</html>
