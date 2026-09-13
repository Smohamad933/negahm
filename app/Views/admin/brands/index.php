<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<header class="page-actions">
  <form class="inline-search" action="<?= e(url('/admin/brands')) ?>" method="get" role="search">
    <label class="sr-only" for="q">جست‌وجو</label>
    <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="جست‌وجوی برند…">
    <button type="submit">جست‌وجو</button>
  </form>
  <a class="btn btn-primary" href="<?= e(url('/admin/brands/create')) ?>">+ افزودن برند</a>
</header>

<section class="panel">
  <header class="panel-head">
    <h2>فهرست برندها</h2>
    <span class="panel-meta"><?= e(jdigits($pager['total'])) ?> مورد</span>
  </header>

  <div class="panel-body panel-body-flush">
    <?php if ($pager['data'] === []): ?>
      <p class="panel-empty">برندی ثبت نشده است. اولین برند را اضافه کنید.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:64px">لوگو</th>
            <th>برند</th>
            <th>دسته‌بندی</th>
            <th style="width:90px">پروژه‌ها</th>
            <th style="width:110px">ترتیب</th>
            <th style="width:230px">وضعیت</th>
            <th style="width:170px">عملیات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pager['data'] as $brand): ?>
          <tr data-row="<?= (int) $brand['id'] ?>">
            <td>
              <span class="thumb">
                <?php if (!empty($brand['logo'])): ?>
                  <img src="<?= e(upload_url($brand['logo'])) ?>" alt="">
                <?php else: ?>
                  <span aria-hidden="true"><?= e(mb_substr($brand['name'], 0, 1)) ?></span>
                <?php endif; ?>
              </span>
            </td>
            <td>
              <strong class="cell-title"><?= e($brand['name']) ?></strong>
              <?php if (!empty($brand['name_en'])): ?>
              <small dir="ltr"><?= e($brand['name_en']) ?></small>
              <?php endif; ?>
              <small class="cell-slug">/brands/<?= e($brand['slug']) ?></small>
            </td>
            <td><?= e($brand['category_title'] ?? '—') ?></td>
            <td><?= e(jdigits($brand['works_count'] ?? 0)) ?></td>
            <td><?= e(jdigits($brand['sort_order'])) ?></td>
            <td>
              <div class="toggle-group">
                <button class="switch <?= (int) $brand['is_published'] === 1 ? 'is-on' : '' ?>" type="button"
                        data-toggle data-url="<?= e(url('/admin/brands/' . $brand['id'] . '/toggle/is_published')) ?>"
                        aria-pressed="<?= (int) $brand['is_published'] === 1 ? 'true' : 'false' ?>">
                  <span class="switch-label">انتشار</span>
                </button>
                <button class="switch <?= (int) $brand['is_featured'] === 1 ? 'is-on' : '' ?>" type="button"
                        data-toggle data-url="<?= e(url('/admin/brands/' . $brand['id'] . '/toggle/is_featured')) ?>"
                        aria-pressed="<?= (int) $brand['is_featured'] === 1 ? 'true' : 'false' ?>">
                  <span class="switch-label">ویژه</span>
                </button>
                <button class="switch <?= (int) $brand['has_dedicated_page'] === 1 ? 'is-on' : '' ?>" type="button"
                        data-toggle data-url="<?= e(url('/admin/brands/' . $brand['id'] . '/toggle/has_dedicated_page')) ?>"
                        aria-pressed="<?= (int) $brand['has_dedicated_page'] === 1 ? 'true' : 'false' ?>">
                  <span class="switch-label">صفحه اختصاصی</span>
                </button>
              </div>
            </td>
            <td>
              <div class="row-actions">
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/admin/brands/' . $brand['id'] . '/edit')) ?>">ویرایش</a>
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/brands/' . $brand['slug'])) ?>" target="_blank" rel="noopener">نمایش ↗</a>
                <form action="<?= e(url('/admin/brands/' . $brand['id'] . '/delete')) ?>" method="post" data-confirm="آیا از حذف برند «<?= e($brand['name']) ?>» مطمئن هستید؟">
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

    <?= pagination_links($pager, '/admin/brands') ?>
    <?php endif; ?>
  </div>
</section>

<?php View::stop(); ?>
