<?php
/**
 * پانوشت سایت — ستون «دسترسی سریع» از مدیریت منوی پنل می‌آید.
 */
$siteName   = $siteName ?? 'نگاه مدیا';
$socials    = $socials ?? [];
$menuFooter = $menuFooter ?? [];
?>
<footer class="site-footer">

  <section class="footer-cta">
    <div class="container footer-cta-inner">
      <div>
        <p class="eyebrow">پروژه بعدی شما</p>
        <h2>بیایید چیزی <em>قابلِ دیدن</em> بسازیم.</h2>
        <p class="footer-about">اگر ایده‌ای دارید یا نمی‌دانید از کجا شروع کنید، با ما صحبت کنید.</p>
      </div>
      <div class="footer-cta-actions">
        <?php if (!empty($sitePhone)): ?>
        <a class="btn btn-primary" href="tel:<?= e($sitePhone) ?>">درخواست مشاوره <span>←</span></a>
        <?php endif; ?>
        <?php if (!empty($siteEmail)): ?>
        <a class="btn btn-ghost" href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?> <span>↗</span></a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <div class="container footer-grid">

    <div class="footer-col">
      <a class="brand" href="<?= e(url('/')) ?>">
        <span class="brand-mark" aria-hidden="true">N</span>
        <span class="brand-text"><strong><?= e($siteName) ?></strong><small><?= e($siteTagline ?? '') ?></small></span>
      </a>
      <p><?= e($footerAbout !== '' ? $footerAbout : 'نگاه مدیا یک تیم خلاق برای برندهایی است که می‌خواهند متفاوت فکر کنند. ما بین استراتژی، طراحی و محتوا پل می‌زنیم.') ?></p>
      <?php if ($socials !== []): ?>
      <div class="socials">
        <?php foreach ($socials as $key => $link): ?>
        <a href="<?= e($link) ?>" target="_blank" rel="noopener nofollow" aria-label="<?= e(social_label($key)) ?>">
          <span aria-hidden="true"><?= e(icon_for_social($key)) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="footer-col">
      <h3 class="footer-title">دسترسی سریع</h3>
      <?= menu_links($menuFooter, 'footer') ?>
    </div>

    <div class="footer-col">
      <h3 class="footer-title">تماس</h3>
      <div class="footer-contact">
        <?php if (!empty($sitePhone)): ?>
        <a href="tel:<?= e($sitePhone) ?>"><?= e(phone_display($sitePhone)) ?></a>
        <?php endif; ?>
        <?php if (!empty($siteEmail)): ?>
        <a href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?></a>
        <?php endif; ?>
        <?php if (!empty($siteAddress)): ?>
        <span><?= e($siteAddress) ?></span>
        <?php endif; ?>
        <?php if (!empty($siteHours)): ?>
        <span><?= e($siteHours) ?></span>
        <?php endif; ?>
      </div>

      <form class="subscribe" action="<?= e(url('/subscribe')) ?>" method="post" data-subscribe>
        <?= csrf_field() ?>
        <label class="sr-only" for="subscribe-email">ایمیل شما</label>
        <input id="subscribe-email" type="email" name="email" placeholder="ایمیل شما برای خبرنامه" required>
        <button type="submit" aria-label="عضویت">←</button>
        <p class="subscribe-note" data-subscribe-note aria-live="polite"></p>
      </form>
    </div>

  </div>

  <div class="footer-bottom">
    <div class="container footer-bottom-inner">
      <p><?= e((string) setting('footer_note', '')) ?: '© ' . jdigits(date('Y')) . ' ' . e($siteName) . ' — تمامی حقوق محفوظ است.' ?></p>
      <p class="footer-meta">
        <a href="<?= e(url('/sitemap.xml')) ?>">نقشه سایت</a>
        <span aria-hidden="true">·</span>
        <a href="<?= e(url('/search')) ?>">جست‌وجو</a>
      </p>
    </div>
  </div>

</footer>

<a class="to-top" href="#top" data-to-top aria-label="بازگشت به بالا">↑</a>
