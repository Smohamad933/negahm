<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<header class="page-actions">
  <form class="inline-search" action="<?= e(url('/admin/media')) ?>" method="get" role="search">
    <label class="sr-only" for="q">جست‌وجو</label>
    <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="جست‌وجوی فایل…">
    <select name="folder" aria-label="پوشه">
      <option value="">همه پوشه‌ها</option>
      <?php foreach ($folders as $folderName): ?>
      <option value="<?= e($folderName) ?>" <?= $folder === $folderName ? 'selected' : '' ?>><?= e($folderName) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">جست‌وجو</button>
  </form>
  <span class="panel-meta">حجم کل: <?= e(\App\Core\Str::humanSize((int) $totalSize)) ?></span>
</header>

<section class="panel">
  <header class="panel-head"><h2>بارگذاری فایل</h2></header>
  <div class="panel-body">
    <form class="upload-form" action="<?= e(url('/admin/media')) ?>" method="post" enctype="multipart/form-data" data-upload>
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-field">
          <label for="files">انتخاب فایل‌ها</label>
          <input id="files" type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.svg,.pdf,.mp4,.webm,.zip">
        </div>
        <div class="form-field">
          <label for="folder">پوشه</label>
          <input id="folder" type="text" name="folder" value="<?= e($folder !== '' ? $folder : 'general') ?>" dir="ltr">
          <small class="field-hint">فقط حروف انگلیسی، عدد و خط تیره.</small>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">بارگذاری</button>
      <p class="upload-note" data-upload-note aria-live="polite"></p>
    </form>
  </div>
</section>

<section class="panel">
  <header class="panel-head">
    <h2>کتابخانه رسانه</h2>
    <span class="panel-meta"><?= e(jdigits($pager['total'])) ?> فایل</span>
  </header>

  <div class="panel-body panel-body-flush">
    <?php if ($pager['data'] === []): ?>
      <p class="panel-empty">فایلی بارگذاری نشده است.</p>
    <?php else: ?>
    <ul class="media-grid">
      <?php foreach ($pager['data'] as $item): ?>
      <?php $isImage = str_starts_with((string) $item['mime'], 'image/'); ?>
      <li class="media-item">
        <span class="media-thumb">
          <?php if ($isImage): ?>
            <img src="<?= e(upload_url($item['path'])) ?>" alt="<?= e($item['alt'] ?: $item['name']) ?>" loading="lazy">
          <?php else: ?>
            <span class="media-ext" aria-hidden="true"><?= e(strtoupper(pathinfo((string) $item['path'], PATHINFO_EXTENSION))) ?></span>
          <?php endif; ?>
        </span>
        <span class="media-meta">
          <strong title="<?= e($item['name']) ?>"><?= e(excerpt((string) $item['name'], 26)) ?></strong>
          <small><?= e(\App\Core\Str::humanSize((int) $item['size'])) ?> · <?= e($item['folder']) ?></small>
        </span>
        <span class="media-actions">
          <button class="btn btn-xs btn-ghost" type="button" data-copy="<?= e(upload_url($item['path'])) ?>">کپی آدرس</button>
          <form action="<?= e(url('/admin/media/' . $item['id'] . '/delete')) ?>" method="post" data-confirm="حذف «<?= e($item['name']) ?>»؟">
            <?= csrf_field() ?>
            <button class="btn btn-xs btn-danger" type="submit">حذف</button>
          </form>
        </span>
      </li>
      <?php endforeach; ?>
    </ul>

    <?= pagination_links($pager, '/admin/media') ?>
    <?php endif; ?>
  </div>
</section>

<?php View::stop(); ?>
