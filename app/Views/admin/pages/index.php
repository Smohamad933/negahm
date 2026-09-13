<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<header class="page-actions">
  <span class="panel-meta"><?= e(jdigits(count($pages))) ?> صفحه</span>
  <a class="btn btn-primary" href="<?= e(url('/admin/pages/create')) ?>">+ افزودن صفحه</a>
</header>

<section class="panel">
  <div class="panel-body panel-body-flush">
    <?php if ($pages === []): ?>
      <p class="panel-empty">صفحه‌ای ثبت نشده است.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>عنوان</th>
            <th style="width:130px">نمایش در منو</th>
            <th style="width:130px">نمایش در فوتر</th>
            <th style="width:110px">وضعیت</th>
            <th style="width:170px">عملیات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pages as $page): ?>
          <tr>
            <td>
              <strong class="cell-title"><?= e($page['title']) ?></strong>
              <small class="cell-slug">/p/<?= e($page['slug']) ?></small>
            </td>
            <td><?= (int) $page['show_in_menu'] === 1 ? 'بله' : '—' ?></td>
            <td><?= (int) $page['show_in_footer'] === 1 ? 'بله' : '—' ?></td>
            <td>
              <span class="status-pill <?= (int) $page['is_published'] === 1 ? 'is-on' : 'is-off' ?>">
                <?= (int) $page['is_published'] === 1 ? 'منتشر شده' : 'پیش‌نویس' ?>
              </span>
            </td>
            <td>
              <div class="row-actions">
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/admin/pages/' . $page['id'] . '/edit')) ?>">ویرایش</a>
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/p/' . $page['slug'])) ?>" target="_blank" rel="noopener">نمایش ↗</a>
                <form action="<?= e(url('/admin/pages/' . $page['id'] . '/delete')) ?>" method="post" data-confirm="حذف صفحه «<?= e($page['title']) ?>»؟">
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
