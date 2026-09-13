<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>شمارنده‌های آماری</h2>
        <span class="panel-meta"><?= e(jdigits(count($items))) ?> شمارنده</span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if ($items === []): ?>
          <p class="panel-empty">شمارنده‌ای ثبت نشده است.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>عنوان</th><th style="width:110px">مقدار</th><th style="width:80px">پسوند</th><th style="width:90px">ترتیب</th><th style="width:110px">وضعیت</th><th style="width:90px"></th></tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><strong class="cell-title"><?= e($item['label']) ?></strong><?php if (!empty($item['description'])): ?><small><?= e($item['description']) ?></small><?php endif; ?></td>
                <td><?= e(jdigits($item['value'])) ?></td>
                <td><?= e($item['suffix'] ?? '—') ?></td>
                <td><?= e(jdigits($item['sort_order'])) ?></td>
                <td><span class="status-pill <?= (int) $item['is_published'] === 1 ? 'is-on' : 'is-off' ?>"><?= (int) $item['is_published'] === 1 ? 'نمایش' : 'مخفی' ?></span></td>
                <td>
                  <form action="<?= e(url('/admin/stats/' . $item['id'] . '/delete')) ?>" method="post" data-confirm="حذف این شمارنده؟">
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
      <header class="panel-head"><h2>افزودن شمارنده</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/stats')) ?>" method="post">
          <?= csrf_field() ?>
          <div class="form-field">
            <label for="label">عنوان <span aria-hidden="true">*</span></label>
            <input id="label" type="text" name="label" required placeholder="سال تجربه">
          </div>
          <div class="form-row">
            <div class="form-field">
              <label for="value">مقدار <span aria-hidden="true">*</span></label>
              <input id="value" type="text" name="value" required placeholder="3">
            </div>
            <div class="form-field">
              <label for="suffix">پسوند</label>
              <input id="suffix" type="text" name="suffix" placeholder="+">
            </div>
          </div>
          <div class="form-field">
            <label for="description">توضیح کوتاه</label>
            <input id="description" type="text" name="description">
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
