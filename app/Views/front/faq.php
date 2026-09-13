<?php
use App\Core\View;
View::extend('layouts.front'); ?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => 'سوالات متداول',
    'lead'   => 'پاسخ پرسش‌هایی که معمولاً پیش از شروع همکاری پرسیده می‌شود. اگر پاسخ سوال شما اینجا نبود، با ما تماس بگیرید.',
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => 'سوالات متداول', 'url' => ''],
    ],
]) ?>

<section class="faq-section">
  <div class="container container-narrow">

    <?php if (empty($faqs)): ?>
      <div class="empty-state"><p>هنوز سوالی ثبت نشده است.</p></div>
    <?php else: ?>
      <div class="accordion">
        <?php foreach ($faqs as $index => $faq): ?>
        <details class="accordion-item" <?= $index === 0 ? 'open' : '' ?>>
          <summary><?= e($faq['question']) ?></summary>
          <div class="accordion-body"><?= \App\Core\Str::nl2p((string) $faq['answer']) ?></div>
        </details>
        <?php endforeach; ?>
      </div>

      <?= json_ld([
          '@type' => 'FAQPage',
          'mainEntity' => array_map(static fn (array $f) => [
              '@type' => 'Question',
              'name'  => $f['question'],
              'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string) $f['answer'])],
          ], $faqs),
      ]) ?>
    <?php endif; ?>

    <div class="cta-band cta-band-sm">
      <div>
        <h2>پاسخ سوال خود را پیدا نکردید؟</h2>
        <p>کافی است یک پیام بفرستید؛ در اولین فرصت پاسخ می‌دهیم.</p>
      </div>
      <a class="btn btn-primary" href="<?= e(url('/contact')) ?>">ارسال پیام <span aria-hidden="true">←</span></a>
    </div>

  </div>
</section>

<?php View::stop(); ?>
