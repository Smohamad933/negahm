<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>مراحل همکاری</h2>
        <span class="panel-meta"><?= e(jdigits(count($items))) ?> مرحله</span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if ($items === []): ?>
          <p class="panel-empty">مرحله‌ای ثبت نشده است.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th style="width:80px">شماره</th><th>عنوان</th><th>توضیح</th><th style="width:90px">ترتیب</th><th style="width:110px">وضعیت</th><th style="width:90px"></th></tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><?= e(jdigits($item['number'] ?: $item['sort_order'])) ?></td>
                <td><strong class="cell-title"><?= e($item['title']) ?></strong></td>
                <td><small><?= e(excerpt((string) $item['body'], 80)) ?></small></td>
                <td><?= e(jdigits($item['sort_order'])) ?></td>
                <td><span class="status-pill <?= (int) $item['is_published'] === 1 ? 'is-on' : 'is-off' ?>"><?= (int) $item['is_published'] === 1 ? 'نمایش' : 'مخفی' ?></span></td>
                <td>
                  <form action="<?= e(url('/admin/process/' . $item['id'] . '/delete')) ?>" method="post" data-confirm="حذف این مرحله؟">
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
      <header class="panel-head"><h2>افزودن مرحله</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/process')) ?>" method="post">
          <?= csrf_field() ?>
          <div class="form-field">
            <label for="title">عنوان <span aria-hidden="true">*</span></label>
            <input id="title" type="text" name="title" required placeholder="شناخت">
          </div>
          <div class="form-field">
            <label for="number">شماره نمایشی</label>
            <input id="number" type="text" name="number" placeholder="۰۱">
          </div>
          <div class="form-field">
            <label for="body">توضیح</label>
            <textarea id="body" name="body" rows="3"></textarea>
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
