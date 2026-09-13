<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<header class="page-actions">
  <form class="inline-search inline-search-wide" action="<?= e(url('/admin/works')) ?>" method="get" role="search">
    <label class="sr-only" for="q">جست‌وجو</label>
    <input id="q" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="جست‌وجوی پروژه…">

    <label class="sr-only" for="brand">برند</label>
    <select id="brand" name="brand">
      <option value="">همه برندها</option>
      <?php foreach ($brands as $brand): ?>
      <option value="<?= (int) $brand['id'] ?>" <?= (int) $filters['brand_id'] === (int) $brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label class="sr-only" for="category">دسته‌بندی</label>
    <select id="category" name="category">
      <option value="">همه دسته‌ها</option>
      <?php foreach ($categories as $category): ?>
      <option value="<?= (int) $category['id'] ?>" <?= (int) $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['title']) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit">اعمال فیلتر</button>
  </form>
  <a class="btn btn-primary" href="<?= e(url('/admin/works/create')) ?>">+ افزودن نمونه‌کار</a>
</header>

<section class="panel">
  <header class="panel-head">
    <h2>فهرست نمونه‌کارها</h2>
    <span class="panel-meta"><?= e(jdigits($pager['total'])) ?> مورد</span>
  </header>

  <div class="panel-body panel-body-flush">
    <?php if ($pager['data'] === []): ?>
      <p class="panel-empty">نمونه‌کاری ثبت نشده است.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:88px">تصویر</th>
            <th>پروژه</th>
            <th>برند</th>
            <th>دسته</th>
            <th style="width:80px">سال</th>
            <th style="width:150px">وضعیت</th>
            <th style="width:170px">عملیات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pager['data'] as $work): ?>
          <tr>
            <td>
              <span class="thumb thumb-wide">
                <?php if (!empty($work['cover'])): ?>
                  <img src="<?= e(upload_url($work['cover'])) ?>" alt="">
                <?php else: ?>
                  <span aria-hidden="true">▣</span>
                <?php endif; ?>
              </span>
            </td>
            <td>
              <strong class="cell-title"><?= e($work['title']) ?></strong>
              <small class="cell-slug">/works/<?= e($work['slug']) ?></small>
            </td>
            <td><?= e($work['brand_name'] ?? $work['client_name'] ?? '—') ?></td>
            <td><?= e($work['category_title'] ?? '—') ?></td>
            <td><?= e(jdigits($work['year'] ?? '—')) ?></td>
            <td>
              <div class="toggle-group">
                <button class="switch <?= (int) $work['is_published'] === 1 ? 'is-on' : '' ?>" type="button"
                        data-toggle data-url="<?= e(url('/admin/works/' . $work['id'] . '/toggle/is_published')) ?>"
                        aria-pressed="<?= (int) $work['is_published'] === 1 ? 'true' : 'false' ?>">
                  <span class="switch-label">انتشار</span>
                </button>
                <button class="switch <?= (int) $work['is_featured'] === 1 ? 'is-on' : '' ?>" type="button"
                        data-toggle data-url="<?= e(url('/admin/works/' . $work['id'] . '/toggle/is_featured')) ?>"
                        aria-pressed="<?= (int) $work['is_featured'] === 1 ? 'true' : 'false' ?>">
                  <span class="switch-label">ویژه</span>
                </button>
              </div>
            </td>
            <td>
              <div class="row-actions">
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/admin/works/' . $work['id'] . '/edit')) ?>">ویرایش</a>
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/works/' . $work['slug'])) ?>" target="_blank" rel="noopener">نمایش ↗</a>
                <form action="<?= e(url('/admin/works/' . $work['id'] . '/delete')) ?>" method="post" data-confirm="آیا از حذف «<?= e($work['title']) ?>» مطمئن هستید؟">
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

    <?= pagination_links($pager, '/admin/works') ?>
    <?php endif; ?>
  </div>
</section>

<?php View::stop(); ?>
