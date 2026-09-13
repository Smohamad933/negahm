<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<header class="page-actions">
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/messages')) ?>">→ بازگشت به صندوق</a>
  <div class="actions-group">
    <form action="<?= e(url('/admin/messages/' . $message['id'] . '/read')) ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="read" value="<?= (int) $message['is_read'] === 1 ? '0' : '1' ?>">
      <button class="btn btn-sm btn-ghost" type="submit"><?= (int) $message['is_read'] === 1 ? 'خوانده‌نشده کن' : 'خوانده‌شده کن' ?></button>
    </form>
    <form action="<?= e(url('/admin/messages/' . $message['id'] . '/archive')) ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="archived" value="<?= (int) $message['is_archived'] === 1 ? '0' : '1' ?>">
      <button class="btn btn-sm btn-ghost" type="submit"><?= (int) $message['is_archived'] === 1 ? 'خروج از بایگانی' : 'بایگانی' ?></button>
    </form>
    <form action="<?= e(url('/admin/messages/' . $message['id'] . '/delete')) ?>" method="post" data-confirm="حذف این پیام؟">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-danger" type="submit">حذف</button>
    </form>
  </div>
</header>

<section class="panel">
  <header class="panel-head">
    <h2><?= e($message['subject'] ?: 'پیام جدید') ?></h2>
    <span class="panel-meta"><?= e(jdate($message['created_at'], 'l، j F Y', true)) ?></span>
  </header>

  <div class="panel-body">
    <dl class="info-grid">
      <div><dt>نام</dt><dd><?= e($message['name']) ?></dd></div>
      <?php if (!empty($message['company'])): ?><div><dt>شرکت</dt><dd><?= e($message['company']) ?></dd></div><?php endif; ?>
      <?php if (!empty($message['phone'])): ?><div><dt>تلفن</dt><dd><a href="tel:<?= e($message['phone']) ?>" dir="ltr"><?= e($message['phone']) ?></a></dd></div><?php endif; ?>
      <?php if (!empty($message['email'])): ?><div><dt>ایمیل</dt><dd><a href="mailto:<?= e($message['email']) ?>" dir="ltr"><?= e($message['email']) ?></a></dd></div><?php endif; ?>
      <?php if (!empty($message['service_title'])): ?><div><dt>خدمت</dt><dd><?= e($message['service_title']) ?></dd></div><?php endif; ?>
      <?php if (!empty($message['budget'])): ?><div><dt>بودجه</dt><dd><?= e($message['budget']) ?></dd></div><?php endif; ?>
      <div><dt>منبع</dt><dd><?= e($message['source']) ?></dd></div>
      <div><dt>IP</dt><dd dir="ltr"><?= e($message['ip']) ?></dd></div>
    </dl>

    <div class="message-body">
      <h3>متن پیام</h3>
      <?= \App\Core\Str::nl2p((string) $message['body']) ?>
    </div>

    <div class="message-actions">
      <?php if (!empty($message['phone'])): ?>
      <a class="btn btn-primary" href="tel:<?= e($message['phone']) ?>">تماس با <?= e($message['name']) ?></a>
      <?php endif; ?>
      <?php if (!empty($message['email'])): ?>
      <a class="btn btn-ghost" href="mailto:<?= e($message['email']) ?>?subject=<?= e(rawurlencode('پاسخ: ' . ($message['subject'] ?: 'درخواست شما'))) ?>">ارسال ایمیل پاسخ</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php View::stop(); ?>
