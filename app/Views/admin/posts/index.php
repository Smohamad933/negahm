<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<header class="page-actions">
  <form class="inline-search" action="<?= e(url('/admin/posts')) ?>" method="get" role="search">
    <label class="sr-only" for="q">جست‌وجو</label>
    <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="جست‌وجوی مقاله…">
    <button type="submit">جست‌وجو</button>
  </form>
  <div class="actions-group">
    <a class="btn btn-ghost" href="<?= e(url('/admin/post-categories')) ?>">دسته‌بندی‌ها</a>
    <a class="btn btn-primary" href="<?= e(url('/admin/posts/create')) ?>">+ افزودن مقاله</a>
  </div>
</header>

<section class="panel">
  <header class="panel-head">
    <h2>مقالات</h2>
    <span class="panel-meta"><?= e(jdigits($pager['total'])) ?> مورد</span>
  </header>

  <div class="panel-body panel-body-flush">
    <?php if ($pager['data'] === []): ?>
      <p class="panel-empty">مقاله‌ای ثبت نشده است.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:88px">تصویر</th>
            <th>عنوان</th>
            <th>دسته</th>
            <th style="width:120px">تاریخ</th>
            <th style="width:90px">بازدید</th>
            <th style="width:110px">وضعیت</th>
            <th style="width:150px">عملیات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pager['data'] as $post): ?>
          <tr>
            <td>
              <span class="thumb thumb-wide">
                <?php if (!empty($post['cover'])): ?><img src="<?= e(upload_url($post['cover'])) ?>" alt=""><?php else: ?><span aria-hidden="true">✎</span><?php endif; ?>
              </span>
            </td>
            <td>
              <strong class="cell-title"><?= e($post['title']) ?></strong>
              <small class="cell-slug">/blog/<?= e($post['slug']) ?></small>
            </td>
            <td><?= e($post['category_title'] ?? '—') ?></td>
            <td><?= e(jdate($post['published_at'] ?? $post['created_at'], 'j F Y')) ?></td>
            <td><?= e(jdigits($post['views'])) ?></td>
            <td>
              <span class="status-pill <?= (int) $post['is_published'] === 1 ? 'is-on' : 'is-off' ?>">
                <?= (int) $post['is_published'] === 1 ? 'منتشر شده' : 'پیش‌نویس' ?>
              </span>
            </td>
            <td>
              <div class="row-actions">
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/admin/posts/' . $post['id'] . '/edit')) ?>">ویرایش</a>
                <form action="<?= e(url('/admin/posts/' . $post['id'] . '/delete')) ?>" method="post" data-confirm="حذف مقاله «<?= e($post['title']) ?>»؟">
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

    <?= pagination_links($pager, '/admin/posts') ?>
    <?php endif; ?>
  </div>
</section>

<?php View::stop(); ?>
