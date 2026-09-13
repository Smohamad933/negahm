<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<header class="page-actions">
  <span class="panel-meta"><?= e(jdigits(count($services))) ?> خدمت ثبت شده</span>
  <a class="btn btn-primary" href="<?= e(url('/admin/services/create')) ?>">+ افزودن خدمت</a>
</header>

<section class="panel">
  <div class="panel-body panel-body-flush">
    <?php if ($services === []): ?>
      <p class="panel-empty">هنوز خدمتی ثبت نشده است.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:60px">آیکون</th>
            <th>خدمت</th>
            <th style="width:100px">ترتیب</th>
            <th style="width:120px">وضعیت</th>
            <th style="width:170px">عملیات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($services as $service): ?>
          <tr>
            <td><span class="icon-cell" aria-hidden="true"><?= e($service['icon'] ?: '✦') ?></span></td>
            <td>
              <strong class="cell-title"><?= e($service['title']) ?></strong>
              <small class="cell-slug">/services/<?= e($service['slug']) ?></small>
            </td>
            <td><?= e(jdigits($service['sort_order'])) ?></td>
            <td>
              <span class="status-pill <?= (int) $service['is_published'] === 1 ? 'is-on' : 'is-off' ?>">
                <?= (int) $service['is_published'] === 1 ? 'منتشر شده' : 'پیش‌نویس' ?>
              </span>
            </td>
            <td>
              <div class="row-actions">
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/admin/services/' . $service['id'] . '/edit')) ?>">ویرایش</a>
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/services/' . $service['slug'])) ?>" target="_blank" rel="noopener">نمایش ↗</a>
                <form action="<?= e(url('/admin/services/' . $service['id'] . '/delete')) ?>" method="post" data-confirm="حذف خدمت «<?= e($service['title']) ?>»؟">
                  <?= csrf_field() ?>
                  <button class="btn btn-xs btn-danger" type="submit">حذف</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php View::stop(); ?>
