<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>سوالات متداول</h2>
        <span class="panel-meta"><?= e(jdigits(count($items))) ?> سوال</span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if ($items === []): ?>
          <p class="panel-empty">سوالی ثبت نشده است.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>سوال</th><th style="width:120px">گروه</th><th style="width:90px">ترتیب</th><th style="width:110px">وضعیت</th><th style="width:90px"></th></tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <strong class="cell-title"><?= e($item['question']) ?></strong>
                  <small><?= e(excerpt((string) $item['answer'], 80)) ?></small>
                </td>
                <td><span class="badge-pill"><?= e($item['group']) ?></span></td>
                <td><?= e(jdigits($item['sort_order'])) ?></td>
                <td><span class="status-pill <?= (int) $item['is_published'] === 1 ? 'is-on' : 'is-off' ?>"><?= (int) $item['is_published'] === 1 ? 'نمایش' : 'مخفی' ?></span></td>
                <td>
                  <form action="<?= e(url('/admin/faqs/' . $item['id'] . '/delete')) ?>" method="post" data-confirm="حذف این سوال؟">
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
      <header class="panel-head"><h2>افزودن سوال</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/faqs')) ?>" method="post">
          <?= csrf_field() ?>
          <div class="form-field">
            <label for="question">سوال <span aria-hidden="true">*</span></label>
            <input id="question" type="text" name="question" required>
          </div>
          <div class="form-field">
            <label for="answer">پاسخ <span aria-hidden="true">*</span></label>
            <textarea id="answer" name="answer" rows="5" required></textarea>
          </div>
          <div class="form-field">
            <label for="group">گروه نمایش</label>
            <select id="group" name="group">
              <option value="general">عمومی (صفحه سوالات)</option>
              <option value="contact">صفحه تماس</option>
              <option value="services">صفحات خدمات</option>
            </select>
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
