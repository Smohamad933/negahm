<?php
/**
 * هدر مشترک صفحات داخلی
 * @var string $title
 * @var string|null $lead
 * @var array<int,array{label:string,url:string}> $crumbs
 */
$title  = $title ?? '';
$lead   = $lead ?? '';
$crumbs = $crumbs ?? [];
?>
<section class="page-hero">
  <div class="container">
    <?php if ($crumbs !== []): ?>
    <nav class="breadcrumb" aria-label="مسیر">
      <ol>
        <?php foreach ($crumbs as $crumb): ?>
        <li>
          <?php if (!empty($crumb['url'])): ?>
            <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
          <?php else: ?>
            <span aria-current="page"><?= e($crumb['label']) ?></span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <?php endif; ?>

    <h1 class="page-title"><?= e($title) ?></h1>
    <?php if ($lead !== ''): ?>
    <p class="page-lead"><?= e($lead) ?></p>
    <?php endif; ?>
  </div>
</section>
