<?php
use App\Core\View;
View::extend('layouts.admin');

$isEdit = $service !== null;
$items  = $items ?? [];
$val = static function (string $key, mixed $default = '') use ($service, $isEdit): string {
    $old = old($key, null);
    if ($old !== null) {
        return (string) $old;
    }
    return (string) ($isEdit ? ($service[$key] ?? $default) : $default);
};
$checked = static function (string $key, bool $default) use ($service, $isEdit): bool {
    if (old($key, null) !== null) {
        return (bool) old($key);
    }
    return $isEdit ? (int) ($service[$key] ?? 0) === 1 : $default;
};
$icons = ['✦', '◌', '↗', '⌁', '+', '◫', '◎', '◆', '✎', '▣', '❖', '⚡'];
?>
<?php View::start('content'); ?>

<header class="page-actions">
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/services')) ?>">→ بازگشت به فهرست</a>
</header>

<form class="form-grid" action="<?= e($action) ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="form-main">
    <section class="panel">
      <header class="panel-head"><h2>اطلاعات خدمت</h2></header>
      <div class="panel-body">

        <div class="form-row">
          <div class="form-field">
            <label for="title">عنوان خدمت <span aria-hidden="true">*</span></label>
            <input id="title" type="text" name="title" value="<?= e($val('title')) ?>" required>
            <?php if (has_error('title')): ?><p class="field-error"><?= e(error_for('title')) ?></p><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="label_en">عنوان لاتین</label>
            <input id="label_en" type="text" name="label_en" value="<?= e($val('label_en')) ?>" dir="ltr" placeholder="Brand Identity">
          </div>
        </div>

        <div class="form-row form-row-3">
          <div class="form-field">
            <label for="slug">آدرس یکتا</label>
            <input id="slug" type="text" name="slug" value="<?= e($val('slug')) ?>" dir="ltr">
          </div>
          <div class="form-field">
            <label for="number">شماره نمایشی</label>
            <input id="number" type="text" name="number" value="<?= e($val('number')) ?>" placeholder="۰۱">
          </div>
          <div class="form-field">
            <label for="icon">آیکون</label>
            <select id="icon" name="icon">
              <?php foreach ($icons as $icon): ?>
              <option value="<?= e($icon) ?>" <?= $val('icon', '✦') === $icon ? 'selected' : '' ?>><?= e($icon) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-field">
          <label for="excerpt">خلاصه</label>
          <textarea id="excerpt" name="excerpt" rows="2" maxlength="500"><?= e($val('excerpt')) ?></textarea>
        </div>

        <div class="form-field">
          <label for="body">توضیحات کامل</label>
          <textarea id="body" name="body" rows="12" class="editor"><?= e($val('body')) ?></textarea>
        </div>

        <div class="form-field">
          <label>آیتم‌های تحویلی</label>
          <div data-repeater>
            <?php $rows = $items !== [] ? $items : ['']; ?>
            <?php foreach ($rows as $index => $item): ?>
            <div class="repeater-row">
              <input type="text" name="items[]" value="<?= e((string) $item) ?>" placeholder="مثال: طراحی لوگو و سیستم گرافیکی">
              <button type="button" class="btn btn-xs btn-danger" data-repeater-remove>حذف</button>
            </div>
            <?php endforeach; ?>
            <button type="button" class="btn btn-xs btn-ghost" data-repeater-add
                    data-template='<div class="repeater-row"><input type="text" name="items[]" placeholder="مثال: طراحی لوگو و سیستم گرافیکی"><button type="button" class="btn btn-xs btn-danger" data-repeater-remove>حذف</button></div>'>
              + افزودن آیتم
            </button>
          </div>
        </div>

        <div class="form-field">
          <label for="price_note">یادداشت قیمت / شرایط</label>
          <input id="price_note" type="text" name="price_note" value="<?= e($val('price_note')) ?>">
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
      <header class="panel-head"><h2>انتشار</h2></header>
      <div class="panel-body">
        <label class="checkbox-line">
          <input type="checkbox" name="is_published" value="1" <?= $checked('is_published', true) ? 'checked' : '' ?>>
          <span>منتشر شده</span>
        </label>
        <label class="checkbox-line">
          <input type="checkbox" name="is_featured" value="1" <?= $checked('is_featured', false) ? 'checked' : '' ?>>
          <span>خدمت ویژه</span>
        </label>
        <div class="form-field">
          <label for="sort_order">ترتیب نمایش</label>
          <input id="sort_order" type="number" name="sort_order" value="<?= e($val('sort_order', '0')) ?>" min="0">
        </div>
        <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'ذخیره تغییرات' : 'ایجاد خدمت' ?></button>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>تصویر</h2></header>
      <div class="panel-body">
        <?php if ($isEdit && !empty($service['image'])): ?>
        <div class="media-preview">
          <img src="<?= e(upload_url($service['image'])) ?>" alt="">
          <label class="checkbox-line"><input type="checkbox" name="remove_image" value="1"><span>حذف تصویر</span></label>
        </div>
        <?php endif; ?>
        <div class="form-field">
          <label for="image">بارگذاری تصویر</label>
          <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>
    </section>
  </aside>
</form>

<?php View::stop(); ?>
