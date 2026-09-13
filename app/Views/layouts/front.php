<?php
use App\Core\View;
/** @var string $pageTitle */
$seoTitle = trim((string) ($seoTitle ?? ''));
$seoDesc  = trim((string) ($seoDesc ?? ''));
$siteName = $siteName ?? 'نگاه مدیا';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($seoTitle !== '' ? $seoTitle . ' | ' . $siteName : ($pageTitle ?? $siteName)) ?></title>
<meta name="description" content="<?= e($seoDesc !== '' ? $seoDesc : ($seoDefault['description'] ?? '')) ?>">
<?php if (!empty($seoDefault['keywords'])): ?>
<meta name="keywords" content="<?= e($seoDefault['keywords']) ?>">
<?php endif; ?>
<meta name="theme-color" content="#1a1a1a">
<link rel="canonical" href="<?= e(site_url($_SERVER['REQUEST_URI'] ?? '/')) ?>">
<?php if (($siteFavicon = (string) setting('site_favicon', '')) !== ''): ?>
<link rel="icon" href="<?= e(upload_url($siteFavicon)) ?>">
<?php endif; ?>

<meta property="og:locale" content="fa_IR">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($pageTitle ?? $siteName) ?>">
<meta property="og:description" content="<?= e($seoDesc !== '' ? $seoDesc : ($seoDefault['description'] ?? '')) ?>">
<meta property="og:url" content="<?= e(site_url($_SERVER['REQUEST_URI'] ?? '/')) ?>">
<meta name="twitter:card" content="summary_large_image">

<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<?php if (!empty($fontCss)): ?>
<style><?= $fontCss ?></style>
<?php endif; ?>

<?= json_ld([
    '@type' => 'Organization',
    '@id'   => site_url('/#organization'),
    'name'  => $siteName,
    'url'   => site_url('/'),
    'description' => $seoDefault['description'] ?? '',
    'contactPoint' => array_filter([
        '@type' => 'ContactPoint',
        'telephone' => $sitePhone ?? '',
        'email' => $siteEmail ?? '',
        'contactType' => 'customer service',
        'areaServed' => 'IR',
        'availableLanguage' => 'fa',
    ]),
]) ?>

<script>document.documentElement.classList.add('js');</script>
</head>
<body id="top" class="<?= e($bodyClass ?? '') ?>">

<a class="skip-link" href="#main">پرش به محتوای اصلی</a>

<?= partial('partials.header') ?>

<main id="main" class="site-main">
<?= View::section('content') ?>
</main>

<?= partial('partials.footer') ?>

<button class="to-top" type="button" aria-label="بازگشت به بالا" data-to-top>↑</button>

<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?php if (!empty($analytics)): ?>
<?= $analytics ?>
<?php endif; ?>
</body>
</html>
