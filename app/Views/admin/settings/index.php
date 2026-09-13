<?php
use App\Core\View;
View::extend('layouts.admin');

$groupLabels = [
    'general' => 'عمومی',
    'home'    => 'صفحه اصلی',
    'about'   => 'درباره ما',
    'contact' => 'تماس',
    'social'  => 'شبکه‌های اجتماعی',
    'seo'     => 'سئو',
    'pages'   => 'سئوی صفحه‌ها',
];

$valueOf = static function (array $values, string $key, string $type): string {
    $row = $values[$key] ?? null;
    if ($row === null) {
        return '';
    }
    if ($type === 'lines') {
        $decoded = json_decode((string) $row['value'], true);
        return is_array($decoded) ? implode("\n", $decoded) : '';
    }
    return (string) ($row['value'] ?? '');
};
?>
<?php View::start('content'); ?>

<ul class="tab-row tab-row-lg">
  <?php foreach ($groups as $groupName): ?>
  <li><a class="tab <?= $group === $groupName ? 'is-active' : '' ?>" href="<?= e(url('/admin/settings?group=' . $groupName)) ?>"><?= e($groupLabels[$groupName] ?? $groupName) ?></a></li>
  <?php endforeach; ?>
</ul>

<form class="panel settings-form" action="<?= e(url('/admin/settings')) ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="group" value="<?= e($group) ?>">

  <header class="panel-head">
    <h2>تنظیمات — <?= e($groupLabels[$group] ?? $group) ?></h2>
    <button class="btn btn-primary btn-sm" type="submit">ذخیره تنظیمات</button>
  </header>

  <div class="panel-body">
    <?php $index = 0; ?>
    <?php foreach ($schema as $field): ?>
      <?php if ($field['group'] !== $group) { continue; } ?>
      <?php $key = $field['key']; ?>
      <?php if ($index > 0): ?><hr class="divider"><?php endif; ?>
      <?php $index++; ?>

      <div class="form-field">
        <label for="set_<?= e($key) ?>"><?= e($field['label']) ?></label>

        <?php if ($field['type'] === 'text'): ?>
          <textarea id="set_<?= e($key) ?>" name="<?= e($key) ?>" rows="4"><?= e($valueOf($values, $key, 'text')) ?></textarea>

        <?php elseif ($field['type'] === 'lines'): ?>
          <textarea id="set_<?= e($key) ?>" name="<?= e($key) ?>" rows="5" placeholder="هر خط یک مورد"><?= e($valueOf($values, $key, 'lines')) ?></textarea>

        <?php elseif ($field['type'] === 'image'): ?>
          <?php $current = $valueOf($values, $key, 'image'); ?>
          <?php if ($current !== ''): ?>
          <div class="media-preview media-preview-sm">
            <img src="<?= e(upload_url($current)) ?>" alt="">
            <label class="checkbox-line"><input type="checkbox" name="<?= e($key) ?>_remove" value="1"><span>حذف تصویر</span></label>
          </div>
          <?php endif; ?>
          <input id="set_<?= e($key) ?>" type="file" name="<?= e($key) ?>_file" accept=".jpg,.jpeg,.png,.webp,.svg,.ico">
          <small class="field-hint">برای تغییر، فایل جدید را انتخاب کنید.</small>

        <?php else: ?>
          <input id="set_<?= e($key) ?>" type="text" name="<?= e($key) ?>" value="<?= e($valueOf($values, $key, 'string')) ?>"
                 <?= in_array($key, ['social_instagram','social_telegram','social_linkedin','social_twitter','social_youtube','social_whatsapp','social_behance','social_aparats','site_url'], true) ? 'dir="ltr"' : '' ?>>
        <?php endif; ?>

        <?php if (!empty($field['hint'])): ?>
        <small class="field-hint"><?= e($field['hint']) ?></small>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <button class="btn btn-primary" type="submit">ذخیره تنظیمات</button>
  </div>
</form>

<?php View::stop(); ?>
