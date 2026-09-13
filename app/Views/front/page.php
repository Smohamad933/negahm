<?php
use App\Core\View;
View::extend('layouts.front'); ?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => $page['title'],
    'lead'   => (string) ($page['subtitle'] ?? ''),
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => $page['title'], 'url' => ''],
    ],
]) ?>

<article class="static-page">
  <div class="container <?= ($page['template'] ?? 'default') === 'full' ? '' : 'container-narrow' ?>">

    <?php if (!empty($page['cover'])): ?>
    <figure class="page-cover">
      <img src="<?= e(upload_url($page['cover'])) ?>" alt="<?= e($page['title']) ?>">
    </figure>
    <?php endif; ?>

    <div class="rich-text rich-text-lg">
      <?= $page['body'] ?>
    </div>

  </div>
</article>

<?php View::stop(); ?>
