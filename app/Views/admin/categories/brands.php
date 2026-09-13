<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>دسته‌بندی برندها</h2>
        <span class="panel-meta"><?= e(jdigits(count($categories))) ?> دسته</span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if ($categories === []): ?>
          <p class="panel-empty">دسته‌بندی ثبت نشده است.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th style="width:70px">شناسه</th><th>عنوان</th><th>نامک</th><th style="width:90px">ترتیب</th><th style="width:110px">وضعیت</th><th style="width:90px"></th></tr></thead>
            <tbody>
              <?php foreach ($categories as $category): ?>
              <tr>
                <td><?= e(jdigits($category['id'])) ?></td>
                <td><strong class="cell-title"><?= e($category['title']) ?></strong><small><?= e(excerpt((string) $category['description'], 50)) ?></small></td>
                <td><code dir="ltr"><?= e($category['slug']) ?></code></td>
                <td><?= e(jdigits($category['sort_order'])) ?></td>
                <td><span class="status-pill <?= (int) $category['is_published'] === 1 ? 'is-on' : 'is-off' ?>"><?= (int) $category['is_published'] === 1 ? 'نمایش' : 'مخفی' ?></span></td>
                <td>
                  <form action="<?= e(url('/admin/brand-categories/' . $category['id'] . '/delete')) ?>" method="post" data-confirm="حذف «<?= e($category['title']) ?>»؟">
                    <?= csrf_field() ?><button class="btn btn-xs btn-danger" type="submit">حذف</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <aside class="form-side">
    <section class="panel">
      <header class="panel-head"><h2>افزودن دسته‌بندی</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/brand-categories')) ?>" method="post">
          <?= csrf_field() ?>
          <div class="form-field">
            <label for="title">عنوان <span aria-hidden="true">*</span></label>
            <input id="title" type="text" name="title" required>
          </div>
          <div class="form-field">
            <label for="slug">نامک</label>
            <input id="slug" type="text" name="slug" dir="ltr" placeholder="اگر خالی بگذارید، از عنوان ساخته می‌شود">
          </div>
          <div class="form-field">
            <label for="description">توضیح</label>
            <textarea id="description" name="description" rows="2"></textarea>
          </div>
          <div class="form-field">
            <label for="sort_order">ترتیب</label>
            <input id="sort_order" type="number" name="sort_order" value="0" min="0">
          </div>
          <label class="checkbox-line"><input type="checkbox" name="is_published" value="1" checked><span>نمایش در سایت</span></label>
          <button class="btn btn-primary btn-block" type="submit">افزودن</button>
        </form>
      </div>
    </section>
  </aside>
</div>

<?php View::stop(); ?>
