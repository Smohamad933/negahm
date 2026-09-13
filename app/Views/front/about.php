<?php
use App\Core\View;
View::extend('layouts.front');
$values = is_array($values ?? null) ? $values : [];
$aboutImage = (string) setting('about_image', '');
?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => 'ما فقط تبلیغ نمی‌کنیم؛ روایت می‌سازیم.',
    'lead'   => $aboutIntro !== '' ? $aboutIntro : 'نگاه مدیا یک تیم خلاق برای برندهایی است که می‌خواهند متفاوت فکر کنند.',
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => 'درباره ما', 'url' => ''],
    ],
]) ?>

<section class="about-story">
  <div class="container about-grid about-grid-lg">
    <div class="about-copy">
      <?php if ($aboutStory !== ''): ?>
        <div class="rich-text" data-reveal><?= \App\Core\Str::nl2p($aboutStory) ?></div>
      <?php else: ?>
        <p class="lead" data-reveal>ما بین استراتژی، طراحی و محتوا پل می‌زنیم تا هر خروجی بخشی از یک تصویر بزرگ‌تر باشد. هر پروژه برای ما یک فرصت است تا یک برند را قابل تشخیص‌تر، قابل فهم‌تر و قابل اعتمادتر کنیم.</p>
        <p data-reveal>باور ما این است که تبلیغ خوب از شناخت مسئله شروع می‌شود، نه از انتخاب رنگ و فونت. به همین دلیل هر همکاری را با جلسه شناخت و تعریف هدف آغاز می‌کنیم و تا اندازه‌گیری نتیجه همراه برند می‌مانیم.</p>
      <?php endif; ?>

      <?php if ($values !== []): ?>
      <ul class="value-list">
        <?php foreach ($values as $value): ?>
        <li data-reveal><?= e($value) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <?php if ($aboutImage !== ''): ?>
    <figure class="about-media" data-reveal>
      <img src="<?= e(upload_url($aboutImage)) ?>" alt="تیم نگاه مدیا">
    </figure>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($stats)): ?>
<section class="about-band">
  <div class="container">
    <ul class="stat-list stat-list-lg">
      <?php foreach ($stats as $stat): ?>
      <li class="stat-item" data-reveal>
        <strong><?= e(jdigits($stat['value'])) ?></strong>
        <span class="stat-suffix" aria-hidden="true"><?= e($stat['suffix']) ?></span>
        <em><?= e($stat['label']) ?></em>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($process)): ?>
<section class="process">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow">چطور همکاری می‌کنیم؟</p>
      <h2 class="section-title">ساده، شفاف، <em>حساب‌شده</em>.</h2>
    </div>
    <ol class="process-list">
      <?php foreach ($process as $index => $step): ?>
      <li class="process-step" data-reveal>
        <span class="process-num"><?= e($step['number'] ?: jdigits(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))) ?></span>
        <h3><?= e($step['title']) ?></h3>
        <p><?= e($step['body']) ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($team)): ?>
<section class="team">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow">تیم</p>
      <h2 class="section-title">آدم‌های پشت <em>نگاه</em>.</h2>
    </div>

    <ul class="team-grid">
      <?php foreach ($team as $member): ?>
      <?php $memberSocials = \App\Models\TeamMember::decodeSocials($member['socials'] ?? null); ?>
      <li class="team-card" data-reveal>
        <span class="team-photo">
          <?php if (!empty($member['photo'])): ?>
            <img src="<?= e(upload_url($member['photo'])) ?>" alt="<?= e($member['name']) ?>" loading="lazy">
          <?php else: ?>
            <span class="avatar-fallback avatar-lg" aria-hidden="true"><?= e(mb_substr($member['name'], 0, 1)) ?></span>
          <?php endif; ?>
        </span>
        <strong><?= e($member['name']) ?></strong>
        <em><?= e($member['role']) ?></em>
        <?php if (!empty($member['bio'])): ?>
        <p><?= e(excerpt((string) $member['bio'], 110)) ?></p>
        <?php endif; ?>
        <?php if ($memberSocials !== []): ?>
        <ul class="social-list social-list-sm">
          <?php foreach ($memberSocials as $key => $link): ?>
          <li><a href="<?= e($link) ?>" target="_blank" rel="noopener nofollow" aria-label="<?= e($key) ?>">
            <span aria-hidden="true"><?= e(icon_for_social($key)) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($marquee)): ?>
<section class="marquee-section">
  <div class="container section-head">
    <p class="eyebrow">همراهان نگاه</p>
    <h2 class="section-title">برندهایی که به ما <em>اعتماد</em> کردند.</h2>
  </div>
  <div class="marquee" data-marquee>
    <div class="marquee-track">
      <?php for ($i = 0; $i < 2; $i++): ?>
        <?php foreach ($marquee as $brand): ?>
        <a class="marquee-item" href="<?= e(url('/brands/' . $brand['slug'])) ?>">
          <?php if (!empty($brand['logo'])): ?>
            <img src="<?= e(upload_url($brand['logo'])) ?>" alt="<?= e($brand['name']) ?>" loading="lazy">
          <?php else: ?>
            <span><?= e($brand['name']) ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      <?php endfor; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($testimonials)): ?>
<section class="testimonials">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow">تجربه همکاری</p>
      <h2 class="section-title">مشتریان ما <em>چه می‌گویند</em>.</h2>
    </div>
    <div class="testimonial-grid">
      <?php foreach ($testimonials as $testimonial): ?>
      <figure class="testimonial-card" data-reveal>
        <blockquote><?= e($testimonial['quote']) ?></blockquote>
        <figcaption>
          <span class="avatar-fallback" aria-hidden="true"><?= e(mb_substr($testimonial['name'], 0, 1)) ?></span>
          <span>
            <strong><?= e($testimonial['name']) ?></strong>
            <small><?= e(trim(($testimonial['role'] ?? '') . (!empty($testimonial['company']) ? ' — ' . $testimonial['company'] : ''))) ?></small>
          </span>
        </figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php View::stop(); ?>
