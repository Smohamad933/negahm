<?php
/**
 * مدیریت فونت سایت — بارگذاری woff2/woff/ttf/otf و تعیین فونت فعال
 */
use App\Core\View;
View::extend('layouts.admin');

$fonts  = $fonts ?? [];
$active = $active ?? null;
?>
<?php View::start('content'); ?>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>فونت‌های سایت</h2>
        <span class="panel-meta">فونت فعال: <?= e(\App\Models\Font::activeLabel()) ?></span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if ($fonts === []): ?>
          <p class="panel-empty">
            هنوز فونتی بارگذاری نشده است؛ سایت از فونت پیش‌فرض (وزیرمتن) استفاده می‌کند.
          </p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>نام فونت</th>
                <th style="width:110px">قالب</th>
                <th style="width:130px">وزن</th>
                <th style="width:110px">وضعیت</th>
                <th style="width:180px"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($fonts as $font): ?>
              <tr>
                <td>
                  <strong class="cell-title"><?= e((string) $font['name']) ?></strong>
                  <small><?= e((string) $font['file']) ?></small>
                </td>
                <td><span class="badge-pill"><?= e((string) $font['format']) ?></span></td>
                <td><?= e(jdigits((string) $font['weight_min'])) ?>–<?= e(jdigits((string) $font['weight_max'])) ?></td>
                <td>
                  <span class="status-pill <?= (int) $font['is_active'] === 1 ? 'is-on' : 'is-off' ?>">
                    <?= (int) $font['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?>
                  </span>
                </td>
                <td class="cell-actions">
                  <?php if ((int) $font['is_active'] !== 1): ?>
                  <form action="<?= e(url('/admin/fonts/' . $font['id'] . '/activate')) ?>" method="post">
                    <?= csrf_field() ?><button class="btn btn-xs btn-primary" type="submit">فعال‌سازی</button>
                  </form>
                  <?php endif; ?>
                  <a class="btn btn-xs" href="<?= e(upload_url((string) $font['file'])) ?>" target="_blank" rel="noopener">نمایش فایل</a>
                  <form action="<?= e(url('/admin/fonts/' . $font['id'] . '/delete')) ?>" method="post" data-confirm="حذف این فونت؟">
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
      <header class="panel-head"><h2>بارگذاری فونت</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/fonts')) ?>" method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <div class="form-field">
            <label for="font-name">نام فونت <span aria-hidden="true">*</span></label>
            <input id="font-name" type="text" name="name" required placeholder="مثلاً: ایران‌سنس">
          </div>

          <div class="form-field">
            <label for="font-file">فایل فونت <span aria-hidden="true">*</span></label>
            <input id="font-file" type="file" name="file" accept=".woff2,.woff,.ttf,.otf" required>
            <small class="form-hint">پسوند مجاز: woff2، woff، ttf، otf</small>
          </div>

          <div class="form-row">
            <div class="form-field">
              <label for="font-wmin">حداقل وزن</label>
              <input id="font-wmin" type="number" name="weight_min" value="400" min="1" max="1000">
            </div>
            <div class="form-field">
              <label for="font-wmax">حداکثر وزن</label>
              <input id="font-wmax" type="number" name="weight_max" value="400" min="1" max="1000">
            </div>
          </div>

          <label class="checkbox-line"><input type="checkbox" name="is_active" value="1" checked><span>بلافاصله فعال شود</span></label>

          <button class="btn btn-primary btn-block" type="submit">بارگذاری</button>
        </form>
      </div>
    </section>

    <?php if ($active !== null): ?>
    <section class="panel">
      <header class="panel-head"><h2>بازگشت به پیش‌فرض</h2></header>
      <div class="panel-body">
        <p class="panel-hint">با این کار هیچ فونتی فعال نمی‌ماند و سایت به فونت پیش‌فرض برمی‌گردد.</p>
        <form action="<?= e(url('/admin/fonts/reset')) ?>" method="post" data-confirm="بازگشت به فونت پیش‌فرض؟">
          <?= csrf_field() ?>
          <button class="btn btn-block" type="submit">بازگشت به فونت پیش‌فرض</button>
        </form>
      </div>
    </section>
    <?php endif; ?>

    <section class="panel">
      <header class="panel-head"><h2>راهنما</h2></header>
      <div class="panel-body">
        <p class="panel-hint">
          اگر فایل فونت شما «متغیر» (variable) است و همه وزن‌ها را دارد، حداقل وزن را ۱۰۰ و
          حداکثر را ۹۰۰ بگذارید تا ضخامت‌های مختلف درست نمایش داده شوند.
        </p>
        <p class="panel-hint">فونت فعال روی کل سایت — هم صفحات عمومی و هم نوشتارها — اعمال می‌شود.</p>
      </div>
    </section>
  </aside>
</div>

<?php View::stop(); ?>
