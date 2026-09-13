<?php
use App\Core\View;
View::extend('layouts.admin');

$isEdit = $page !== null;
$val = static function (string $key, mixed $default = '') use ($page, $isEdit): string {
    $old = old($key, null);
    if ($old !== null) {
        return (string) $old;
    }
    return (string) ($isEdit ? ($page[$key] ?? $default) : $default);
};
$checked = static function (string $key, bool $default) use ($page, $isEdit): bool {
    if (old($key, null) !== null) {
        return (bool) old($key);
    }
    return $isEdit ? (int) ($page[$key] ?? 0) === 1 : $default;
};
?>
<?php View::start('content'); ?>

<header class="page-actions">
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/pages')) ?>">→ بازگشت به فهرست</a>
</header>

<form class="form-grid" action="<?= e($action) ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="form-main">
    <section class="panel">
      <header class="panel-head"><h2>محتوای صفحه</h2></header>
      <div class="panel-body">
        <div class="form-row">
          <div class="form-field">
            <label for="title">عنوان <span aria-hidden="true">*</span></label>
            <input id="title" type="text" name="title" value="<?= e($val('title')) ?>" required>
          </div>
          <div class="form-field">
            <label for="slug">آدرس یکتا</label>
            <input id="slug" type="text" name="slug" value="<?= e($val('slug')) ?>" dir="ltr">
          </div>
        </div>

        <div class="form-field">
          <label for="subtitle">زیرعنوان</label>
          <input id="subtitle" type="text" name="subtitle" value="<?= e($val('subtitle')) ?>">
        </div>

        <div class="form-field">
          <label for="body">متن صفحه</label>
          <textarea id="body" name="body" rows="18" class="editor"><?= e($val('body')) ?></textarea>
        </div>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>سئو</h2></header>
      <div class="panel-body">
        <div class="form-field">
          <label for="seo_title">عنوان سئو</label>
          <input id="seo_title" type="text" name="seo_title" value="<?= e($val('seo_title')) ?>">
        </div>
        <div class="form-field">
          <label for="seo_description">توضیحات متا</label>
          <textarea id="seo_description" name="seo_description" rows="3" maxlength="500"><?= e($val('seo_description')) ?></textarea>
        </div>
      </div>
    </section>
  </div>

  <aside class="form-side">
    <section class="panel">
      <header class="panel-head"><h2>تنظیمات نمایش</h2></header>
      <div class="panel-body">
        <label class="checkbox-line">
          <input type="checkbox" name="is_published" value="1" <?= $checked('is_published', true) ? 'checked' : '' ?>>
          <span>منتشر شده</span>
        </label>
        <label class="checkbox-line">
          <input type="checkbox" name="show_in_menu" value="1" <?= $checked('show_in_menu', false) ? 'checked' : '' ?>>
          <span>نمایش در منوی اصلی</span>
        </label>
        <label class="checkbox-line">
          <input type="checkbox" name="show_in_footer" value="1" <?= $checked('show_in_footer', false) ? 'checked' : '' ?>>
          <span>نمایش در فوتر</span>
        </label>

        <div class="form-field">
          <label for="template">قالب نمایش</label>
          <select id="template" name="template">
            <?php foreach (['default' => 'پیش‌فرض (ستون باریک)', 'full' => 'تمام‌عرض', 'sidebar' => 'با ستون کناری'] as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $val('template', 'default') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-field">
          <label for="sort_order">ترتیب</label>
          <input id="sort_order" type="number" name="sort_order" value="<?= e($val('sort_order', '0')) ?>" min="0">
        </div>

        <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'ذخیره تغییرات' : 'ایجاد صفحه' ?></button>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>تصویر شاخص</h2></header>
      <div class="panel-body">
        <?php if ($isEdit && !empty($page['cover'])): ?>
        <div class="media-preview">
          <img src="<?= e(upload_url($page['cover'])) ?>" alt="">
          <label class="checkbox-line"><input type="checkbox" name="remove_cover" value="1"><span>حذف تصویر</span></label>
        </div>
        <?php endif; ?>
        <div class="form-field">
          <label for="cover">بارگذاری تصویر</label>
          <input id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>
    </section>
  </aside>
</form>

<?php View::stop(); ?>
