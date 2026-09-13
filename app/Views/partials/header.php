<?php
/**
 * هدر سایت — ساختار مطابق negahm.ir
 * منو از پنل مدیریت می‌آید (MenuItem) و برای دسکتاپ و موبایل جداگانه رندر می‌شود.
 */
$siteName     = $siteName ?? 'نگاه مدیا';
$menuDesktop  = $menuDesktop ?? [];
$menuMobile   = $menuMobile ?? [];
$logo         = (string) setting('site_logo', '');
$headerCta    = (string) setting('header_cta_text', 'شروع یک پروژه');
$headerCtaUrl = (string) setting('header_cta_url', '/contact');

/*
 * هدر fixed و متنش سفید است. بیشتر صفحه‌ها بالای تیره دارند (هیرو در خانه و
 * صفحهٔ خدمت، و page-hero در بقیه)، ولی جزئیات نمونه‌کار و جزئیات نوشته
 * مستقیم روی پس‌زمینهٔ روشن body شروع می‌شوند؛ آنجا منوی سفید خوانده نمی‌شد.
 * پس فقط همان دو صفحه هدر روشن می‌گیرند.
 */
$isDetail  = (is_current('/works') && !is_current('/works', true))
          || (is_current('/blog') && !is_current('/blog', true));
$headerCls = 'site-header' . ($isDetail ? ' is-light' : '');
?>
<header class="<?= e($headerCls) ?>" data-header>
  <div class="container nav-wrap">

    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?>">
      <?php if ($logo !== ''): ?>
        <img class="brand-logo" src="<?= e(upload_url($logo)) ?>" alt="<?= e($siteName) ?>">
      <?php else: ?>
        <span class="brand-mark" aria-hidden="true">N</span>
        <span class="brand-text">
          <strong><?= e($siteName) ?></strong>
          <small><?= e($siteTagline ?? 'آژانس خلاق و تبلیغاتی') ?></small>
        </span>
      <?php endif; ?>
    </a>

    <nav class="desktop-nav" data-nav aria-label="منوی اصلی">
      <?= menu_links($menuDesktop, 'desktop') ?>
    </nav>

    <?php if ($headerCta !== ''): ?>
    <a class="nav-cta" href="<?= e(menu_link_href($headerCtaUrl)) ?>">
      <?= e($headerCta) ?> <span class="arrow" aria-hidden="true">←</span>
    </a>
    <?php endif; ?>

    <button class="menu-toggle" type="button" data-nav-toggle aria-expanded="false" aria-label="باز و بسته کردن منو">
      <span></span><span></span>
    </button>

    <nav class="mobile-nav" aria-label="منوی موبایل">
      <?= menu_links($menuMobile, 'mobile') ?>
      <a class="mobile-call" href="tel:<?= e(preg_replace('/\D/', '', (string) ($sitePhone ?? ''))) ?>">تماس: <?= e($sitePhone ?? '') ?></a>
      <a class="mobile-call mobile-call-ghost" href="<?= e(url('/search')) ?>">جست‌وجو در سایت</a>
    </nav>

  </div>
</header>
