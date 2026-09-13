<?php
use App\Core\View;
View::extend('layouts.admin');

$isEdit = $post !== null;
$val = static function (string $key, mixed $default = '') use ($post, $isEdit): string {
    $old = old($key, null);
    if ($old !== null) {
        return (string) $old;
    }
    return (string) ($isEdit ? ($post[$key] ?? $default) : $default);
};
$checked = static function (string $key, bool $default) use ($post, $isEdit): bool {
    if (old($key, null) !== null) {
        return (bool) old($key);
    }
    return $isEdit ? (int) ($post[$key] ?? 0) === 1 : $default;
};
?>
<?php View::start('content'); ?>

<header class="page-actions">
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/posts')) ?>">→ بازگشت به فهرست</a>
  <?php if ($isEdit): ?>
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/blog/' . $post['slug'])) ?>" target="_blank" rel="noopener">نمایش مقاله ↗</a>
  <?php endif; ?>
</header>

<form class="form-grid" action="<?= e($action) ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="form-main">
    <section class="panel">
      <header class="panel-head"><h2>محتوای مقاله</h2></header>
      <div class="panel-body">
        <div class="form-field">
          <label for="title">عنوان <span aria-hidden="true">*</span></label>
          <input id="title" type="text" name="title" value="<?= e($val('title')) ?>" required>
          <?php if (has_error('title')): ?><p class="field-error"><?= e(error_for('title')) ?></p><?php endif; ?>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="slug">آدرس یکتا</label>
            <input id="slug" type="text" name="slug" value="<?= e($val('slug')) ?>" dir="ltr">
          </div>
          <div class="form-field">
            <label for="category_id">دسته‌بندی</label>
            <select id="category_id" name="category_id">
              <option value="">— بدون دسته —</option>
              <?php foreach ($categories as $category): ?>
              <option value="<?= (int) $category['id'] ?>" <?= (int) $val('category_id') === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-field">
          <label for="excerpt">خلاصه</label>
          <textarea id="excerpt" name="excerpt" rows="2" maxlength="500"><?= e($val('excerpt')) ?></textarea>
        </div>

        <div class="form-field">
          <label for="body">متن مقاله</label>
          <textarea id="body" name="body" rows="18" class="editor"><?= e($val('body')) ?></textarea>
          <small class="field-hint">از تگ‌های HTML استفاده کنید؛ خروجی در صفحه مقاله با استایل مناسب نمایش داده می‌شود.</small>
        </div>

        <div class="form-field">
          <label for="tags">برچسب‌ها</label>
          <input id="tags" type="text" name="tags" value="<?= e($val('tags')) ?>" placeholder="برندینگ، تبلیغات، طراحی">
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
          <span>مقاله ویژه</span>
        </label>
        <div class="form-field">
          <label for="published_at">تاریخ انتشار</label>
          <input id="published_at" type="datetime-local" name="published_at"
                 value="<?= e($isEdit && !empty($post['published_at']) ? date('Y-m-d\TH:i', (int) strtotime((string) $post['published_at'])) : '') ?>">
        </div>
        <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'ذخیره تغییرات' : 'انتشار مقاله' ?></button>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>تصویر شاخص</h2></header>
      <div class="panel-body">
        <?php if ($isEdit && !empty($post['cover'])): ?>
        <div class="media-preview">
          <img src="<?= e(upload_url($post['cover'])) ?>" alt="">
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
