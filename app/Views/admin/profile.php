<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head"><h2>اطلاعات حساب کاربری</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/profile')) ?>" method="post">
          <?= csrf_field() ?>

          <div class="form-row">
            <div class="form-field">
              <label for="name">نام و نام خانوادگی <span aria-hidden="true">*</span></label>
              <input id="name" type="text" name="name" value="<?= e((string) old('name', $user['name'])) ?>" required>
              <?php if (has_error('name')): ?><p class="field-error"><?= e(error_for('name')) ?></p><?php endif; ?>
            </div>
            <div class="form-field">
              <label for="username">نام کاربری</label>
              <input id="username" type="text" value="<?= e($user['username']) ?>" dir="ltr" disabled>
              <small class="field-hint">نام کاربری قابل تغییر نیست.</small>
            </div>
          </div>

          <div class="form-field">
            <label for="email">ایمیل</label>
            <input id="email" type="email" name="email" value="<?= e((string) old('email', $user['email'])) ?>" dir="ltr">
            <?php if (has_error('email')): ?><p class="field-error"><?= e(error_for('email')) ?></p><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="bio">درباره من</label>
            <textarea id="bio" name="bio" rows="3"><?= e((string) old('bio', $user['bio'])) ?></textarea>
          </div>

          <hr class="divider">
          <h3 class="subsection-title">تغییر رمز عبور</h3>
          <p class="panel-empty panel-empty-sm">اگر قصد تغییر رمز ندارید، این بخش را خالی بگذارید.</p>

          <div class="form-row">
            <div class="form-field">
              <label for="password">رمز عبور جدید</label>
              <input id="password" type="password" name="password" autocomplete="new-password" minlength="8">
              <?php if (has_error('password')): ?><p class="field-error"><?= e(error_for('password')) ?></p><?php endif; ?>
            </div>
            <div class="form-field">
              <label for="password_confirmation">تکرار رمز عبور</label>
              <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8">
            </div>
          </div>

          <button class="btn btn-primary" type="submit">ذخیره تغییرات</button>
        </form>
      </div>
    </section>
  </div>

  <aside class="form-side">
    <section class="panel">
      <header class="panel-head"><h2>اطلاعات نشست</h2></header>
      <div class="panel-body">
        <dl class="info-grid info-grid-single">
          <div><dt>نقش</dt><dd><?= $user['role'] === 'admin' ? 'مدیر' : 'ویرایشگر' ?></dd></div>
          <div><dt>وضعیت</dt><dd><?= (int) $user['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?></dd></div>
          <div><dt>آخرین ورود</dt><dd><?= e($user['last_login_at'] ? jdate($user['last_login_at'], 'j F Y', true) : '—') ?></dd></div>
          <div><dt>عضویت</dt><dd><?= e(jdate($user['created_at'], 'j F Y')) ?></dd></div>
        </dl>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>آخرین فعالیت‌های شما</h2></header>
      <div class="panel-body">
        <?php if (empty($activities)): ?>
          <p class="panel-empty">فعالیتی ثبت نشده است.</p>
        <?php else: ?>
        <ul class="activity-list activity-list-sm">
          <?php foreach ($activities as $activity): ?>
          <li>
            <span class="activity-dot" aria-hidden="true"></span>
            <span class="activity-body">
              <strong><?= e($activity['description'] ?: $activity['action']) ?></strong>
              <small><?= e(\App\Core\Jalali::ago($activity['created_at'])) ?></small>
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </section>
  </aside>
</div>

<?php View::stop(); ?>
