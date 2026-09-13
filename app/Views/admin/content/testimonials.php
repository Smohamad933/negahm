<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>نظرات مشتریان</h2>
        <span class="panel-meta"><?= e(jdigits(count($items))) ?> نظر</span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if ($items === []): ?>
          <p class="panel-empty">نظری ثبت نشده است.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr><th>نام</th><th>نظر</th><th style="width:90px">امتیاز</th><th style="width:90px">ترتیب</th><th style="width:110px">وضعیت</th><th style="width:90px"></th></tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <strong class="cell-title"><?= e($item['name']) ?></strong>
                  <small><?= e(trim(($item['role'] ?? '') . (!empty($item['company']) ? ' — ' . $item['company'] : ''))) ?></small>
                </td>
                <td><small><?= e(excerpt((string) $item['quote'], 90)) ?></small></td>
                <td><?= e(jdigits($item['rating'])) ?>/۵</td>
                <td><?= e(jdigits($item['sort_order'])) ?></td>
                <td><span class="status-pill <?= (int) $item['is_published'] === 1 ? 'is-on' : 'is-off' ?>"><?= (int) $item['is_published'] === 1 ? 'نمایش' : 'مخفی' ?></span></td>
                <td>
                  <form action="<?= e(url('/admin/testimonials/' . $item['id'] . '/delete')) ?>" method="post" data-confirm="حذف این نظر؟">
                    <?= csrf_field() ?>
                    <button class="btn btn-xs btn-danger" type="submit">حذف</button>
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
      <header class="panel-head"><h2>افزودن نظر</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/testimonials')) ?>" method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="form-field">
            <label for="name">نام <span aria-hidden="true">*</span></label>
            <input id="name" type="text" name="name" required>
          </div>
          <div class="form-field">
            <label for="role">سمت</label>
            <input id="role" type="text" name="role" placeholder="مدیرعامل">
          </div>
          <div class="form-field">
            <label for="company">شرکت / برند</label>
            <input id="company" type="text" name="company">
          </div>
          <div class="form-field">
            <label for="quote">متن نظر <span aria-hidden="true">*</span></label>
            <textarea id="quote" name="quote" rows="4" required></textarea>
          </div>
          <div class="form-field">
            <label for="avatar">تصویر</label>
            <input id="avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp">
          </div>
          <div class="form-row">
            <div class="form-field">
              <label for="rating">امتیاز</label>
              <select id="rating" name="rating">
                <?php foreach ([5, 4, 3, 2, 1] as $score): ?>
                <option value="<?= $score ?>"><?= e(jdigits($score)) ?> از ۵</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-field">
              <label for="sort_order">ترتیب</label>
              <input id="sort_order" type="number" name="sort_order" value="0" min="0">
            </div>
          </div>
          <label class="checkbox-line">
            <input type="checkbox" name="is_published" value="1" checked>
            <span>نمایش در سایت</span>
          </label>
          <button class="btn btn-primary btn-block" type="submit">افزودن</button>
        </form>
      </div>
    </section>
  </aside>
</div>

<?php View::stop(); ?>
