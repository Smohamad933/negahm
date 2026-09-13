<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<section class="panel">
  <header class="panel-head">
    <h2>گزارش فعالیت‌ها</h2>
    <span class="panel-meta"><?= e(jdigits($pager['total'])) ?> رویداد</span>
  </header>

  <div class="panel-body panel-body-flush">
    <?php if ($pager['data'] === []): ?>
      <p class="panel-empty">رویدادی ثبت نشده است.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table table-compact">
        <thead>
          <tr><th style="width:190px">زمان</th><th>کاربر</th><th>عملیات</th><th>شرح</th><th style="width:130px">IP</th></tr>
        </thead>
        <tbody>
          <?php foreach ($pager['data'] as $activity): ?>
          <tr>
            <td><small><?= e(jdate($activity['created_at'], 'j F Y', true)) ?></small></td>
            <td><?= e($activity['user_name'] ?? 'سیستم') ?></td>
            <td><span class="badge-pill"><?= e($activity['action']) ?></span></td>
            <td><small><?= e($activity['description'] ?: '—') ?></small></td>
            <td><small dir="ltr"><?= e($activity['ip']) ?></small></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?= pagination_links($pager, '/admin/activity') ?>
    <?php endif; ?>
  </div>
</section>

<?php View::stop(); ?>
