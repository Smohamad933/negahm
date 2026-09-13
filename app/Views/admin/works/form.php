<?php
use App\Core\View;
View::extend('layouts.admin');

$isEdit = $work !== null;
$gallery = $gallery ?? [];
$val = static function (string $key, mixed $default = '') use ($work, $isEdit): string {
    $old = old($key, null);
    if ($old !== null) {
        return (string) $old;
    }
    return (string) ($isEdit ? ($work[$key] ?? $default) : $default);
};
$checked = static function (string $key, bool $default) use ($work, $isEdit): bool {
    if (old($key, null) !== null) {
        return (bool) old($key);
    }
    return $isEdit ? (int) ($work[$key] ?? 0) === 1 : $default;
};
?>
<?php View::start('content'); ?>

<header class="page-actions">
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/works')) ?>">→ بازگشت به فهرست</a>
  <?php if ($isEdit): ?>
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/works/' . $work['slug'])) ?>" target="_blank" rel="noopener">نمایش پروژه ↗</a>
  <?php endif; ?>
</header>

<form class="form-grid" action="<?= e($action) ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="form-main">

    <section class="panel">
      <header class="panel-head"><h2>اطلاعات پروژه</h2></header>
      <div class="panel-body">

        <div class="form-row">
          <div class="form-field">
            <label for="title">عنوان پروژه <span aria-hidden="true">*</span></label>
            <input id="title" type="text" name="title" value="<?= e($val('title')) ?>" required>
            <?php if (has_error('title')): ?><p class="field-error"><?= e(error_for('title')) ?></p><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="label_en">عنوان لاتین (اختیاری)</label>
            <input id="label_en" type="text" name="label_en" value="<?= e($val('label_en')) ?>" dir="ltr" placeholder="Brand Identity">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="slug">آدرس یکتا (نامک)</label>
            <input id="slug" type="text" name="slug" value="<?= e($val('slug')) ?>" dir="ltr">
            <?php if (has_error('slug')): ?><p class="field-error"><?= e(error_for('slug')) ?></p><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="year">سال اجرا</label>
            <input id="year" type="text" name="year" value="<?= e($val('year')) ?>" placeholder="۱۴۰۳">
          </div>
        </div>

        <div class="form-row form-row-3">
          <div class="form-field">
            <label for="brand_id">برند / کارفرما</label>
            <select id="brand_id" name="brand_id">
              <option value="">— انتخاب برند —</option>
              <?php foreach ($brands as $brand): ?>
              <option value="<?= (int) $brand['id'] ?>" <?= (int) $val('brand_id') === (int) $brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="field-hint">با انتخاب برند، پروژه در صفحه اختصاصی آن برند نمایش داده می‌شود.</small>
          </div>

          <div class="form-field">
            <label for="category_id">دسته‌بندی</label>
            <select id="category_id" name="category_id">
              <option value="">— انتخاب دسته —</option>
              <?php foreach ($categories as $category): ?>
              <option value="<?= (int) $category['id'] ?>" <?= (int) $val('category_id') === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field">
            <label for="service_id">خدمت مرتبط</label>
            <select id="service_id" name="service_id">
              <option value="">— انتخاب خدمت —</option>
              <?php foreach ($services as $service): ?>
              <option value="<?= (int) $service['id'] ?>" <?= (int) $val('service_id') === (int) $service['id'] ? 'selected' : '' ?>><?= e($service['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="client_name">نام کارفرما (اگر برند ثبت نشده)</label>
            <input id="client_name" type="text" name="client_name" value="<?= e($val('client_name')) ?>">
          </div>

          <div class="form-field">
            <label for="duration">مدت زمان پروژه</label>
            <input id="duration" type="text" name="duration" value="<?= e($val('duration')) ?>" placeholder="مثال: ۶ هفته">
          </div>
        </div>

        <div class="form-field">
          <label for="services_list">خدمات ارائه‌شده</label>
          <input id="services_list" type="text" name="services_list" value="<?= e($val('services_list')) ?>" placeholder="هویت بصری، عکاسی، طراحی سایت">
        </div>

        <div class="form-field">
          <label for="excerpt">خلاصه پروژه</label>
          <textarea id="excerpt" name="excerpt" rows="2" maxlength="500"><?= e($val('excerpt')) ?></textarea>
        </div>

        <div class="form-field">
          <label for="body">شرح کامل پروژه</label>
          <textarea id="body" name="body" rows="10" class="editor"><?= e($val('body')) ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="challenge">چالش</label>
            <textarea id="challenge" name="challenge" rows="4"><?= e($val('challenge')) ?></textarea>
          </div>
          <div class="form-field">
            <label for="solution">راه‌حل</label>
            <textarea id="solution" name="solution" rows="4"><?= e($val('solution')) ?></textarea>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="result">نتیجه</label>
            <textarea id="result" name="result" rows="4"><?= e($val('result')) ?></textarea>
          </div>
          <div class="form-field">
            <label for="tags">برچسب‌ها</label>
            <input id="tags" type="text" name="tags" value="<?= e($val('tags')) ?>" placeholder="با کاما جدا کنید">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="video_url">آدرس ویدئو (embed)</label>
            <input id="video_url" type="url" name="video_url" value="<?= e($val('video_url')) ?>" dir="ltr">
          </div>
          <div class="form-field">
            <label for="link_url">لینک بیرونی پروژه</label>
            <input id="link_url" type="url" name="link_url" value="<?= e($val('link_url')) ?>" dir="ltr">
          </div>
        </div>

      </div>
    </section>

    <?php if ($isEdit): ?>
    <section class="panel">
      <header class="panel-head"><h2>گالری تصاویر پروژه</h2></header>
      <div class="panel-body">
        <?php if ($gallery === []): ?>
          <p class="panel-empty">هنوز تصویری در گالری نیست.</p>
        <?php else: ?>
        <ul class="gallery-admin">
          <?php foreach ($gallery as $item): ?>
          <li class="gallery-admin-item">
            <img src="<?= e(upload_url($item['path'])) ?>" alt="<?= e($item['alt'] ?: '') ?>">
            <span class="gallery-admin-meta"><?= e($item['caption'] ?: $item['alt'] ?: basename((string) $item['path'])) ?></span>
            <form action="<?= e(url('/admin/work-images/' . $item['id'] . '/delete')) ?>" method="post" data-confirm="حذف این تصویر؟">
              <?= csrf_field() ?>
              <button class="btn btn-xs btn-danger" type="submit">حذف</button>
            </form>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

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
          <span>منتشر شده در سایت</span>
        </label>
        <label class="checkbox-line">
          <input type="checkbox" name="is_featured" value="1" <?= $checked('is_featured', false) ? 'checked' : '' ?>>
          <span>نمایش در نمونه‌کارهای ویژه</span>
        </label>

        <div class="form-field">
          <label for="sort_order">ترتیب نمایش</label>
          <input id="sort_order" type="number" name="sort_order" value="<?= e($val('sort_order', '0')) ?>" min="0">
        </div>

        <div class="form-field">
          <label for="published_at">تاریخ انتشار</label>
          <input id="published_at" type="datetime-local" name="published_at"
                 value="<?= e($isEdit && !empty($work['published_at']) ? date('Y-m-d\TH:i', (int) strtotime((string) $work['published_at'])) : '') ?>">
        </div>

        <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'ذخیره تغییرات' : 'ایجاد نمونه‌کار' ?></button>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>تصویر شاخص</h2></header>
      <div class="panel-body">
        <?php if ($isEdit && !empty($work['cover'])): ?>
        <div class="media-preview">
          <img src="<?= e(upload_url($work['cover'])) ?>" alt="تصویر شاخص">
          <label class="checkbox-line">
            <input type="checkbox" name="remove_cover" value="1">
            <span>حذف تصویر فعلی</span>
          </label>
        </div>
        <?php endif; ?>
        <div class="form-field">
          <label for="cover">بارگذاری تصویر شاخص</label>
          <input id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>افزودن تصاویر گالری</h2></header>
      <div class="panel-body">
        <div class="form-field">
          <label for="gallery">انتخاب چند تصویر</label>
          <input id="gallery" type="file" name="gallery[]" accept=".jpg,.jpeg,.png,.webp" multiple>
          <small class="field-hint">می‌توانید هم‌زمان چند تصویر انتخاب کنید.</small>
        </div>
        <?php if (!$isEdit): ?>
        <p class="panel-empty panel-empty-sm">پس از ایجاد نمونه‌کار می‌توانید گالری را مدیریت کنید.</p>
        <?php endif; ?>
      </div>
    </section>

  </aside>
</form>

<?php View::stop(); ?>
