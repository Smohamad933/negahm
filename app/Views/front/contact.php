<?php
use App\Core\View;
View::extend('layouts.front'); ?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => 'بیایید چیزی قابلِ دیدن بسازیم.',
    'lead'   => 'اگر ایده‌ای دارید یا نمی‌دانید از کجا شروع کنید، با ما صحبت کنید. اولین جلسه مشاوره رایگان است.',
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => 'تماس با ما', 'url' => ''],
    ],
]) ?>

<section class="contact">
  <div class="container contact-grid">

    <div class="contact-form-wrap">
      <form class="contact-form" action="<?= e(url('/contact')) ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <h2>فرم درخواست همکاری</h2>

        <div class="form-row">
          <div class="form-field">
            <label for="c-name">نام و نام خانوادگی <span aria-hidden="true">*</span></label>
            <input id="c-name" type="text" name="name" value="<?= e((string) old('name')) ?>" required autocomplete="name">
            <?php if (has_error('name')): ?><p class="field-error"><?= e(error_for('name')) ?></p><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="c-phone">شماره تماس <span aria-hidden="true">*</span></label>
            <input id="c-phone" type="tel" name="phone" value="<?= e((string) old('phone')) ?>" required
                   placeholder="۰۹۱۲۳۴۵۶۷۸۹" autocomplete="tel" dir="ltr">
            <?php if (has_error('phone')): ?><p class="field-error"><?= e(error_for('phone')) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="c-email">ایمیل</label>
            <input id="c-email" type="email" name="email" value="<?= e((string) old('email')) ?>" autocomplete="email" dir="ltr">
            <?php if (has_error('email')): ?><p class="field-error"><?= e(error_for('email')) ?></p><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="c-company">نام کسب‌وکار</label>
            <input id="c-company" type="text" name="company" value="<?= e((string) old('company')) ?>" autocomplete="organization">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="c-service">خدمت مورد نظر</label>
            <select id="c-service" name="service_id">
              <option value="">انتخاب کنید…</option>
              <?php $selectedService = (int) ($_GET['service'] ?? old('service_id', 0)); ?>
              <?php foreach ($services as $service): ?>
              <option value="<?= (int) $service['id'] ?>" <?= $selectedService === (int) $service['id'] ? 'selected' : '' ?>>
                <?= e($service['title']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field">
            <label for="c-budget">بودجه تقریبی</label>
            <select id="c-budget" name="budget">
              <?php foreach (['', 'زیر ۵۰ میلیون تومان', '۵۰ تا ۱۵۰ میلیون تومان', '۱۵۰ تا ۵۰۰ میلیون تومان', 'بیش از ۵۰۰ میلیون تومان', 'هنوز مشخص نیست'] as $i => $option): ?>
              <option value="<?= e($option) ?>" <?= (string) old('budget') === $option && $i > 0 ? 'selected' : '' ?>>
                <?= $i === 0 ? 'انتخاب کنید…' : e($option) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-field">
          <label for="c-subject">موضوع</label>
          <input id="c-subject" type="text" name="subject" value="<?= e((string) old('subject')) ?>">
        </div>

        <div class="form-field">
          <label for="c-message">درباره پروژه‌تان بنویسید <span aria-hidden="true">*</span></label>
          <textarea id="c-message" name="message" rows="6" required placeholder="هدف، مخاطب، زمان‌بندی و هر اطلاعات دیگری که برای شروع لازم است…"><?= e((string) old('message')) ?></textarea>
          <?php if (has_error('body')): ?><p class="field-error"><?= e(error_for('body')) ?></p><?php endif; ?>
        </div>

        <!-- تله هرزنامه — برای کاربر نمایش داده نمی‌شود -->
        <div class="hp-field" aria-hidden="true">
          <label for="c-website">وب‌سایت</label>
          <input id="c-website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <button class="btn btn-primary btn-lg btn-block" type="submit">ارسال درخواست <span aria-hidden="true">←</span></button>
        <p class="form-note">با ارسال این فرم، اطلاعات شما فقط برای پیگیری همان درخواست استفاده می‌شود.</p>
      </form>
    </div>

    <aside class="contact-info">
      <div class="side-card">
        <h3>راه‌های ارتباطی</h3>
        <ul class="contact-list contact-list-lg">
          <?php if (!empty($sitePhone)): ?>
          <li><span>تلفن</span><a href="tel:<?= e($sitePhone) ?>"><?= e(phone_display($sitePhone)) ?></a></li>
          <?php endif; ?>
          <?php if (!empty($siteEmail)): ?>
          <li><span>ایمیل</span><a href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?></a></li>
          <?php endif; ?>
          <?php if (!empty($siteWhatsapp)): ?>
          <li><span>واتس‌اپ</span><a href="<?= e(str_starts_with((string) $siteWhatsapp, 'http') ? $siteWhatsapp : 'https://wa.me/' . preg_replace('/\D/', '', (string) $siteWhatsapp)) ?>" target="_blank" rel="noopener">گفت‌وگو در واتس‌اپ</a></li>
          <?php endif; ?>
          <?php if (!empty($siteAddress)): ?>
          <li><span>آدرس</span><em><?= e($siteAddress) ?></em></li>
          <?php endif; ?>
          <?php if (!empty($siteHours)): ?>
          <li><span>ساعات کاری</span><em><?= e($siteHours) ?></em></li>
          <?php endif; ?>
        </ul>

        <?php if (!empty($socials)): ?>
        <h3>ما را دنبال کنید</h3>
        <ul class="social-list social-list-side">
          <?php foreach ($socials as $key => $link): ?>
          <li><a href="<?= e($link) ?>" target="_blank" rel="noopener nofollow">
            <span aria-hidden="true"><?= e(icon_for_social($key)) ?></span> <?= e(social_label($key)) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

      <?php if (!empty($siteMapEmbed)): ?>
      <div class="side-card side-card-map">
        <?= $siteMapEmbed ?>
      </div>
      <?php endif; ?>
    </aside>

  </div>
</section>

<?php if (!empty($faqs)): ?>
<section class="faq-section">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow">سوالات متداول</p>
      <h2 class="section-title-sm">پیش از تماس، این‌ها را بخوانید</h2>
    </div>
    <div class="accordion">
      <?php foreach ($faqs as $index => $faq): ?>
      <details class="accordion-item" <?= $index === 0 ? 'open' : '' ?>>
        <summary><?= e($faq['question']) ?></summary>
        <div class="accordion-body"><?= \App\Core\Str::nl2p((string) $faq['answer']) ?></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?= json_ld([
    '@type' => 'ContactPage',
    'name'  => 'تماس با ' . ($siteName ?? 'نگاه مدیا'),
    'url'   => site_url('/contact'),
]) ?>

<?php View::stop(); ?>
