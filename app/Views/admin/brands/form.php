<?php
use App\Core\View;
View::extend('layouts.admin');

$socialKeys = ['instagram', 'telegram', 'linkedin', 'twitter', 'youtube', 'whatsapp', 'behance', 'aparats'];
$socials    = $socials ?? [];
$works      = $works ?? [];
$isEdit     = $brand !== null;
$val        = static function (string $key, mixed $default = '') use ($brand, $isEdit): string {
    $old = old($key, null);
    if ($old !== null) {
        return (string) $old;
    }
    return (string) ($isEdit ? ($brand[$key] ?? $default) : $default);
};
$checked = static function (string $key, bool $default) use ($brand, $isEdit): bool {
    if (old($key, null) !== null) {
        return (bool) old($key);
    }
    return $isEdit ? (int) ($brand[$key] ?? 0) === 1 : $default;
};
?>
<?php View::start('content'); ?>

<header class="page-actions">
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/brands')) ?>">→ بازگشت به فهرست</a>
  <?php if ($isEdit): ?>
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/brands/' . $brand['slug'])) ?>" target="_blank" rel="noopener">نمایش صفحه برند ↗</a>
  <?php endif; ?>
</header>

<form class="form-grid" action="<?= e($action) ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="form-main">

    <section class="panel">
      <header class="panel-head"><h2>اطلاعات اصلی برند</h2></header>
      <div class="panel-body">

        <div class="form-row">
          <div class="form-field">
            <label for="name">نام برند <span aria-hidden="true">*</span></label>
            <input id="name" type="text" name="name" value="<?= e($val('name')) ?>" required>
            <?php if (has_error('name')): ?><p class="field-error"><?= e(error_for('name')) ?></p><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="name_en">نام لاتین برند</label>
            <input id="name_en" type="text" name="name_en" value="<?= e($val('name_en')) ?>" dir="ltr">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="slug">آدرس یکتا (نامک)</label>
            <input id="slug" type="text" name="slug" value="<?= e($val('slug')) ?>" dir="ltr"
                   placeholder="اگر خالی بگذارید، از نام برند ساخته می‌شود">
            <?php if (has_error('slug')): ?><p class="field-error"><?= e(error_for('slug')) ?></p><?php endif; ?>
          </div>

          <div class="form-field">
            <label for="category_id">دسته‌بندی</label>
            <select id="category_id" name="category_id">
              <option value="">— بدون دسته‌بندی —</option>
              <?php foreach ($categories as $category): ?>
              <option value="<?= (int) $category['id'] ?>" <?= (int) $val('category_id') === (int) $category['id'] ? 'selected' : '' ?>>
                <?= e($category['title']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="industry">حوزه فعالیت</label>
            <input id="industry" type="text" name="industry" value="<?= e($val('industry')) ?>" placeholder="مثال: طلا و جواهر">
          </div>

          <div class="form-field">
            <label for="location">شهر / موقعیت</label>
            <input id="location" type="text" name="location" value="<?= e($val('location')) ?>" placeholder="مثال: اهواز">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="website">وب‌سایت برند</label>
            <input id="website" type="url" name="website" value="<?= e($val('website')) ?>" dir="ltr" placeholder="https://">
          </div>

          <div class="form-field">
            <label for="started_at">شروع همکاری</label>
            <input id="started_at" type="text" name="started_at" value="<?= e($val('started_at')) ?>" placeholder="مثال: ۱۴۰۲">
          </div>
        </div>

        <div class="form-field">
          <label for="excerpt">خلاصه کوتاه</label>
          <textarea id="excerpt" name="excerpt" rows="2" maxlength="500"><?= e($val('excerpt')) ?></textarea>
          <small class="field-hint">در کارت‌ها و نتایج جست‌وجو نمایش داده می‌شود.</small>
        </div>

        <div class="form-field">
          <label for="body">معرفی کامل برند</label>
          <textarea id="body" name="body" rows="10" class="editor"><?= e($val('body')) ?></textarea>
          <small class="field-hint">می‌توانید از تگ‌های HTML مثل &lt;h2&gt;، &lt;p&gt;، &lt;ul&gt; و &lt;img&gt; استفاده کنید.</small>
        </div>

        <div class="form-field">
          <label for="video_url">آدرس ویدئو (آپارات / یوتیوب — حالت embed)</label>
          <input id="video_url" type="url" name="video_url" value="<?= e($val('video_url')) ?>" dir="ltr" placeholder="https://www.aparat.com/video/video/embed/videohash/...">
        </div>

        <div class="form-field">
          <label for="tags">برچسب‌ها</label>
          <input id="tags" type="text" name="tags" value="<?= e($val('tags')) ?>" placeholder="با کاما جدا کنید: هویت بصری، کمپین، عکاسی">
        </div>

      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>شبکه‌های اجتماعی برند</h2></header>
      <div class="panel-body">
        <div class="form-row form-row-3">
          <?php foreach ($socialKeys as $key): ?>
          <div class="form-field">
            <label for="social_<?= e($key) ?>"><?= e(social_label($key)) ?></label>
            <input id="social_<?= e($key) ?>" type="url" name="socials[<?= e($key) ?>]" dir="ltr"
                   value="<?= e((string) ($socials[$key] ?? '')) ?>">
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>سئو صفحه اختصاصی برند</h2></header>
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

    <?php if ($isEdit && $works !== []): ?>
    <section class="panel">
      <header class="panel-head">
        <h2>پروژه‌های این برند</h2>
        <a class="panel-link" href="<?= e(url('/admin/works/create')) ?>">+ افزودن پروژه</a>
      </header>
      <div class="panel-body panel-body-flush">
        <div class="table-wrap">
          <table class="table table-compact">
            <thead><tr><th>عنوان</th><th>دسته</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($works as $work): ?>
              <tr>
                <td><?= e($work['title']) ?></td>
                <td><?= e($work['category_title'] ?? '—') ?></td>
                <td><?= (int) $work['is_published'] === 1 ? 'منتشر شده' : 'پیش‌نویس' ?></td>
                <td><a class="btn btn-xs btn-ghost" href="<?= e(url('/admin/works/' . $work['id'] . '/edit')) ?>">ویرایش</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    <?php endif; ?>

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
          <input type="checkbox" name="has_dedicated_page" value="1" <?= $checked('has_dedicated_page', true) ? 'checked' : '' ?>>
          <span>داشتن صفحه اختصاصی</span>
        </label>
        <label class="checkbox-line">
          <input type="checkbox" name="is_featured" value="1" <?= $checked('is_featured', false) ? 'checked' : '' ?>>
          <span>نمایش در برندهای ویژه</span>
        </label>
        <label class="checkbox-line">
          <input type="checkbox" name="show_in_marquee" value="1" <?= $checked('show_in_marquee', true) ? 'checked' : '' ?>>
          <span>نمایش در نوار متحرک صفحه اصلی</span>
        </label>

        <div class="form-field">
          <label for="sort_order">ترتیب نمایش</label>
          <input id="sort_order" type="number" name="sort_order" value="<?= e($val('sort_order', '0')) ?>" min="0">
          <small class="field-hint">عدد کوچک‌تر، جایگاه بالاتر.</small>
        </div>

        <button class="btn btn-primary btn-block" type="submit"><?= $isEdit ? 'ذخیره تغییرات' : 'ایجاد برند' ?></button>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>لوگو</h2></header>
      <div class="panel-body">
        <?php if ($isEdit && !empty($brand['logo'])): ?>
        <div class="media-preview">
          <img src="<?= e(upload_url($brand['logo'])) ?>" alt="لوگو">
          <label class="checkbox-line">
            <input type="checkbox" name="remove_logo" value="1">
            <span>حذف لوگوی فعلی</span>
          </label>
        </div>
        <?php endif; ?>
        <div class="form-field">
          <label for="logo">بارگذاری لوگو</label>
          <input id="logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.svg">
          <small class="field-hint">PNG یا SVG با پس‌زمینه شفاف پیشنهاد می‌شود.</small>
        </div>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>تصویر شاخص</h2></header>
      <div class="panel-body">
        <?php if ($isEdit && !empty($brand['cover'])): ?>
        <div class="media-preview">
          <img src="<?= e(upload_url($brand['cover'])) ?>" alt="تصویر شاخص">
          <label class="checkbox-line">
            <input type="checkbox" name="remove_cover" value="1">
            <span>حذف تصویر فعلی</span>
          </label>
        </div>
        <?php endif; ?>
        <div class="form-field">
          <label for="cover">بارگذاری تصویر</label>
          <input id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">
          <small class="field-hint">ابعاد پیشنهادی: ۱۶۰۰×۹۰۰ پیکسل.</small>
        </div>
      </div>
    </section>

  </aside>
</form>

<?php View::stop(); ?>
