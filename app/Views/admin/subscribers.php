<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<section class="panel">
  <header class="panel-head">
    <h2>اعضای خبرنامه</h2>
    <span class="panel-meta"><?= e(jdigits($total)) ?> عضو</span>
  </header>

  <div class="panel-body panel-body-flush">
    <?php if ($items === []): ?>
      <p class="panel-empty">هنوز کسی در خبرنامه عضو نشده است.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table table-compact">
        <thead><tr><th style="width:70px">شناسه</th><th>ایمیل</th><th style="width:150px">IP</th><th style="width:170px">تاریخ عضویت</th><th style="width:110px">وضعیت</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td><?= e(jdigits($item['id'])) ?></td>
            <td><span dir="ltr"><?= e($item['email']) ?></span></td>
            <td><small dir="ltr"><?= e($item['ip'] ?: '—') ?></small></td>
            <td><small><?= e(jdate($item['created_at'], 'j F Y', true)) ?></small></td>
            <td><span class="status-pill <?= (int) $item['is_active'] === 1 ? 'is-on' : 'is-off' ?>"><?= (int) $item['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php View::stop(); ?>
