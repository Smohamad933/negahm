<?php
/**
 * مدیریت منوی سایت
 * افزودن/حذف/ویرایش آیتم، ترتیب، و کنترل نمایش در دسکتاپ و موبایل
 */
use App\Core\View;
View::extend('layouts.admin');

$items     = $items ?? [];
$types     = $types ?? [];
$routes    = $routes ?? [];
$positions = $positions ?? [];
$options   = $options ?? [];
$position  = $position ?? 'header';

/** نام فیلد انتخاب رکورد مرجع برای هر نوع */
$optionKeys = [
    'page'           => 'pages',
    'service'        => 'services',
    'work'           => 'works',
    'work_category'  => 'workCategories',
    'brand'          => 'brands',
    'brand_category' => 'brandCategories',
    'post'           => 'posts',
    'post_category'  => 'postCategories',
];
?>
<?php View::start('content'); ?>

<div class="panel-tabs">
  <?php foreach ($positions as $key => $label): ?>
  <a class="panel-tab<?= $key === $position ? ' is-active' : '' ?>"
     href="<?= e(url('/admin/menu?position=' . $key)) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>آیتم‌های منو</h2>
        <span class="panel-meta"><?= e(jdigits(count($items))) ?> آیتم</span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if ($items === []): ?>
          <p class="panel-empty">هنوز آیتمی در این منو نیست. از فرم کنار، اولین آیتم را اضافه کنید.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>عنوان</th>
                <th style="width:130px">نوع</th>
                <th style="width:80px">دسکتاپ</th>
                <th style="width:80px">موبایل</th>
                <th style="width:90px">وضعیت</th>
                <th style="width:150px"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <strong class="cell-title">
                    <?php if ((int) ($item['parent_id'] ?? 0) > 0): ?><span aria-hidden="true">↳ </span><?php endif; ?>
                    <?= e($item['label']) ?>
                  </strong>
                  <small><?= e((string) $item['href']) ?></small>
                </td>
                <td><span class="badge-pill"><?= e($types[(string) $item['type']]['label'] ?? (string) $item['type']) ?></span></td>

                <?php foreach (['show_desktop', 'show_mobile'] as $col): ?>
                <td>
                  <form action="<?= e(url('/admin/menu/' . $item['id'] . '/toggle/' . $col)) ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="status-pill <?= (int) $item[$col] === 1 ? 'is-on' : 'is-off' ?>" type="submit"
                            title="کلیک برای تغییر"><?= (int) $item[$col] === 1 ? 'نمایش' : 'مخفی' ?></button>
                  </form>
                </td>
                <?php endforeach; ?>

                <td>
                  <form action="<?= e(url('/admin/menu/' . $item['id'] . '/toggle/is_active')) ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="status-pill <?= (int) $item['is_active'] === 1 ? 'is-on' : 'is-off' ?>" type="submit">
                      <?= (int) $item['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?>
                    </button>
                  </form>
                </td>

                <td class="cell-actions">
                  <details class="row-edit">
                    <summary class="btn btn-xs">ویرایش</summary>
                    <form class="row-edit-form" action="<?= e(url('/admin/menu/' . $item['id'])) ?>" method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="position" value="<?= e((string) $item['position']) ?>">
                      <div class="form-field">
                        <label>عنوان</label>
                        <input type="text" name="title" value="<?= e((string) $item['title']) ?>" placeholder="خالی = عنوان خود رکورد">
                      </div>
                      <div class="form-field">
                        <label>آدرس (برای نوع «آدرس دلخواه»)</label>
                        <input type="text" name="url" value="<?= e((string) $item['url']) ?>">
                      </div>
                      <div class="form-field">
                        <label>زیرمجموعهٔ</label>
                        <select name="parent_id">
                          <option value="0">— بدون والد (سطح اول) —</option>
                          <?php foreach ($items as $candidate): ?>
                            <?php if ((int) $candidate['id'] === (int) $item['id']): continue; endif; ?>
                            <option value="<?= (int) $candidate['id'] ?>" <?= (int) ($item['parent_id'] ?? 0) === (int) $candidate['id'] ? 'selected' : '' ?>>
                              <?= e($candidate['label']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-field">
                        <label>ترتیب</label>
                        <input type="number" name="sort_order" value="<?= (int) $item['sort_order'] ?>" min="0">
                      </div>
                      <label class="checkbox-line"><input type="checkbox" name="show_desktop" value="1" <?= (int) $item['show_desktop'] === 1 ? 'checked' : '' ?>><span>در دسکتاپ</span></label>
                      <label class="checkbox-line"><input type="checkbox" name="show_mobile" value="1" <?= (int) $item['show_mobile'] === 1 ? 'checked' : '' ?>><span>در موبایل</span></label>
                      <label class="checkbox-line"><input type="checkbox" name="opens_new" value="1" <?= (int) $item['opens_new'] === 1 ? 'checked' : '' ?>><span>در تب جدید باز شود</span></label>
                      <label class="checkbox-line"><input type="checkbox" name="is_active" value="1" <?= (int) $item['is_active'] === 1 ? 'checked' : '' ?>><span>فعال</span></label>
                      <button class="btn btn-primary btn-block" type="submit">ذخیره تغییرات</button>
                    </form>
                  </details>

                  <form action="<?= e(url('/admin/menu/' . $item['id'] . '/delete')) ?>" method="post" data-confirm="حذف این آیتم منو؟">
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
      <header class="panel-head"><h2>افزودن آیتم</h2></header>
      <div class="panel-body">
        <form action="<?= e(url('/admin/menu')) ?>" method="post" data-menu-form>
          <?= csrf_field() ?>
          <input type="hidden" name="position" value="<?= e($position) ?>">

          <div class="form-field">
            <label for="menu-type">نوع آیتم</label>
            <select id="menu-type" name="type" data-menu-type>
              <?php foreach ($types as $key => $meta): ?>
              <option value="<?= e($key) ?>" data-label="<?= e($meta['label']) ?>"><?= e($meta['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field" data-field="route">
            <label for="menu-route">صفحه</label>
            <select id="menu-route" name="route_url" data-menu-url>
              <?php foreach ($routes as $path => $label): ?>
              <option value="<?= e($path) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field" data-field="custom">
            <label for="menu-url">آدرس</label>
            <input id="menu-url" type="text" name="url" placeholder="/about یا https://example.com">
          </div>

          <?php foreach ($optionKeys as $type => $key): ?>
          <div class="form-field" data-field="<?= e($type) ?>">
            <label><?= e($types[$type]['label']) ?></label>
            <select name="ref_<?= e($type) ?>" data-menu-ref>
              <option value="0">— انتخاب کنید —</option>
              <?php foreach ((array) ($options[$key] ?? []) as $row): ?>
              <option value="<?= (int) $row['id'] ?>"><?= e((string) ($row['title'] ?? $row['name'] ?? ('#' . $row['id']))) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endforeach; ?>

          <div class="form-field">
            <label for="menu-title">عنوان نمایشی</label>
            <input id="menu-title" type="text" name="title" placeholder="خالی = عنوان خود رکورد">
          </div>

          <div class="form-field">
            <label for="menu-parent">زیرمجموعهٔ</label>
            <select id="menu-parent" name="parent_id">
              <option value="0">— بدون والد (سطح اول) —</option>
              <?php foreach ($items as $candidate): ?>
              <option value="<?= (int) $candidate['id'] ?>"><?= e($candidate['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field">
            <label for="menu-sort">ترتیب</label>
            <input id="menu-sort" type="number" name="sort_order" value="0" min="0">
          </div>

          <label class="checkbox-line"><input type="checkbox" name="show_desktop" value="1" checked><span>نمایش در دسکتاپ</span></label>
          <label class="checkbox-line"><input type="checkbox" name="show_mobile" value="1" checked><span>نمایش در موبایل</span></label>
          <label class="checkbox-line"><input type="checkbox" name="opens_new" value="1"><span>در تب جدید باز شود</span></label>
          <label class="checkbox-line"><input type="checkbox" name="is_active" value="1" checked><span>فعال</span></label>

          <button class="btn btn-primary btn-block" type="submit">افزودن به منو</button>
        </form>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>راهنما</h2></header>
      <div class="panel-body">
        <p class="panel-hint">
          «دسکتاپ» و «موبایل» به شما اجازه می‌دهند منوی موبایل را خلوت‌تر از دسکتاپ نگه دارید؛
          مثلاً «درباره ما» فقط در دسکتاپ نمایش داده شود.
        </p>
        <p class="panel-hint">
          اگر عنوان را خالی بگذارید، عنوان خود صفحه/خدمت/برند در منو نمایش داده می‌شود و با
          تغییر آن، منو هم به‌روز می‌ماند.
        </p>
      </div>
    </section>
  </aside>
</div>

<script>
(function () {
  var type = document.querySelector('[data-menu-type]');
  if (!type) { return; }
  var fields = document.querySelectorAll('[data-field]');

  function sync() {
    var value = type.value;
    fields.forEach(function (field) {
      field.style.display = field.getAttribute('data-field') === value ? '' : 'none';
    });
  }

  type.addEventListener('change', sync);
  sync();
})();
</script>

<?php View::stop(); ?>
