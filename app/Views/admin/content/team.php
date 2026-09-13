<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>اعضای تیم</h2>
        <span class="panel-meta"><?= e(jdigits(count($items))) ?> عضو</span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if ($items === []): ?>
          <p class="panel-empty">هنوز عضوی اضافه نشده است.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th style="width:64px">تصویر</th>
                <th>نام</th>
                <th>نقش</th>
                <th style="width:90px">ترتیب</th>
                <th style="width:110px">وضعیت</th>
                <th style="width:90px"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><span class="thumb"><?php if (!empty($item['photo'])): ?><img src="<?= e(upload_url($item['photo'])) ?>" alt=""><?php else: ?><span aria-hidden="true">☺</span><?php endif; ?></span></td>
                <td><strong class="cell-title"><?= e($item['name']) ?></strong></td>
                <td><?= e($item['role'] ?? '—') ?></td>
                <td><?= e(jdigits($item['sort_order'])) ?></td>
                <td><span class="status-pill <?= (int) $item['is_published'] === 1 ? 'is-on' : 'is-off' ?>"><?= (int) $item['is_published'] === 1 ? 'نمایش' : 'مخفی' ?></span></td>
                <td>
                  <form action="<?= e(url('/admin/team/' . $item['id'] . '/delete')) ?>" method="post" data-confirm="حذف «<?= e($item['name']) ?>»؟">
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
      <header class="panel-head"><h2>افزودن عضو جدید</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/team')) ?>" method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="form-field">
            <label for="name">نام و نام خانوادگی <span aria-hidden="true">*</span></label>
            <input id="name" type="text" name="name" required>
          </div>
          <div class="form-field">
            <label for="role">نقش</label>
            <input id="role" type="text" name="role" placeholder="مثال: مدیر خلاقیت">
          </div>
          <div class="form-field">
            <label for="email">ایمیل</label>
            <input id="email" type="email" name="email" dir="ltr">
          </div>
          <div class="form-field">
            <label for="bio">درباره</label>
            <textarea id="bio" name="bio" rows="3"></textarea>
          </div>
          <div class="form-field">
            <label for="photo">تصویر</label>
            <input id="photo" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
          </div>
          <div class="form-field">
            <label for="sort_order">ترتیب</label>
            <input id="sort_order" type="number" name="sort_order" value="0" min="0">
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
