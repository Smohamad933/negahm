<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>اسلایدر صفحه اصلی</h2>
        <span class="panel-meta"><?= e(jdigits(count($items))) ?> اسلاید</span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if ($items === []): ?>
          <p class="panel-empty">اسلایدی ثبت نشده است. در صورت خالی بودن، بخش معرفی ثابت نمایش داده می‌شود.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th style="width:88px">تصویر</th><th>عنوان</th><th style="width:90px">ترتیب</th><th style="width:110px">وضعیت</th><th style="width:90px"></th></tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><span class="thumb thumb-wide"><?php if (!empty($item['image'])): ?><img src="<?= e(upload_url($item['image'])) ?>" alt=""><?php else: ?><span aria-hidden="true">▤</span><?php endif; ?></span></td>
                <td>
                  <strong class="cell-title"><?= e($item['title'] ?: 'بدون عنوان') ?></strong>
                  <small><?= e(excerpt((string) $item['subtitle'], 60)) ?></small>
                </td>
                <td><?= e(jdigits($item['sort_order'])) ?></td>
                <td><span class="status-pill <?= (int) $item['is_active'] === 1 ? 'is-on' : 'is-off' ?>"><?= (int) $item['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?></span></td>
                <td>
                  <form action="<?= e(url('/admin/sliders/' . $item['id'] . '/delete')) ?>" method="post" data-confirm="حذف این اسلاید؟">
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
      <header class="panel-head"><h2>افزودن اسلاید</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/sliders')) ?>" method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="form-field">
            <label for="title">عنوان</label>
            <input id="title" type="text" name="title">
          </div>
          <div class="form-field">
            <label for="subtitle">زیرعنوان</label>
            <textarea id="subtitle" name="subtitle" rows="2"></textarea>
          </div>
          <div class="form-field">
            <label for="image">تصویر</label>
            <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
          </div>
          <div class="form-field">
            <label for="link">لینک دکمه</label>
            <input id="link" type="text" name="link" dir="ltr" placeholder="/works">
          </div>
          <div class="form-field">
            <label for="button_text">متن دکمه</label>
            <input id="button_text" type="text" name="button_text" placeholder="شروع پروژه">
          </div>
          <div class="form-field">
            <label for="sort_order">ترتیب</label>
            <input id="sort_order" type="number" name="sort_order" value="0" min="0">
          </div>
          <label class="checkbox-line"><input type="checkbox" name="is_active" value="1" checked><span>فعال</span></label>
          <button class="btn btn-primary btn-block" type="submit">افزودن</button>
        </form>
      </div>
    </section>
  </aside>
</div>

<?php View::stop(); ?>
