<?php
use App\Core\View;
View::extend('layouts.front');
$tags    = $tags ?? [];
$related = $related ?? [];
$latest  = $latest ?? [];
?>
<?php View::start('content'); ?>

<article class="post-detail">
  <div class="container">

    <nav class="breadcrumb" aria-label="مسیر">
      <ol>
        <li><a href="<?= e(url('/')) ?>">خانه</a></li>
        <li><a href="<?= e(url('/blog')) ?>">بلاگ</a></li>
        <?php if (!empty($post['category_slug'])): ?>
        <li><a href="<?= e(url('/blog/category/' . $post['category_slug'])) ?>"><?= e($post['category_title']) ?></a></li>
        <?php endif; ?>
        <li><span aria-current="page"><?= e(excerpt((string) $post['title'], 40)) ?></span></li>
      </ol>
    </nav>

    <header class="post-head">
      <?php if (!empty($post['category_title'])): ?>
      <p class="eyebrow"><?= e($post['category_title']) ?></p>
      <?php endif; ?>

      <h1 class="page-title"><?= e($post['title']) ?></h1>

      <?php if (!empty($post['excerpt'])): ?>
      <p class="page-lead"><?= e($post['excerpt']) ?></p>
      <?php endif; ?>

      <ul class="post-meta">
        <li><time datetime="<?= e(date('Y-m-d', (int) strtotime((string) $post['published_at']))) ?>"><?= e(jdate($post['published_at'], 'j F Y')) ?></time></li>
        <?php if (!empty($post['author_name'])): ?>
        <li>نویسنده: <em><?= e($post['author_name']) ?></em></li>
        <?php endif; ?>
        <li><?= e(jdigits($post['reading_time'])) ?> دقیقه مطالعه</li>
        <li><?= e(jdigits($post['views'])) ?> بازدید</li>
      </ul>
    </header>

    <?php if (!empty($post['cover'])): ?>
    <figure class="post-cover">
      <img src="<?= e(upload_url($post['cover'])) ?>" alt="<?= e($post['title']) ?>">
    </figure>
    <?php endif; ?>

    <div class="rich-text rich-text-lg post-body" data-reveal>
      <?= $post['body'] ?>
    </div>

    <?php if ($tags !== []): ?>
    <ul class="tag-row tag-row-lg">
      <?php foreach ($tags as $tag): ?>
      <li><a href="<?= e(url('/blog?q=' . urlencode($tag))) ?>"><?= e($tag) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <section class="cta-band cta-band-sm">
      <div>
        <h2>نیاز به یک تیم خلاق دارید؟</h2>
        <p>از استراتژی تا اجرا، کنار شما هستیم.</p>
      </div>
      <a class="btn btn-primary" href="<?= e(url('/contact')) ?>">شروع گفت‌وگو <span aria-hidden="true">←</span></a>
    </section>

    <?php if ($related !== []): ?>
    <section class="related-posts">
      <div class="section-head">
        <p class="eyebrow">ادامه مطالعه</p>
        <h2 class="section-title-sm">نوشته‌های مرتبط</h2>
      </div>
      <div class="post-grid">
        <?php foreach ($related as $item): ?>
        <a class="post-card" href="<?= e(url('/blog/' . $item['slug'])) ?>">
          <span class="post-media">
            <?php if (!empty($item['cover'])): ?><img src="<?= e(upload_url($item['cover'])) ?>" alt="" loading="lazy"><?php endif; ?>
          </span>
          <span class="post-body">
            <strong><?= e($item['title']) ?></strong>
            <small><?= e(excerpt((string) ($item['excerpt'] ?: $item['body']), 90)) ?></small>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($latest !== []): ?>
    <section class="related-posts">
      <div class="section-head section-head-row">
        <div>
          <p class="eyebrow">تازه‌ها</p>
          <h2 class="section-title-sm">آخرین نوشته‌ها</h2>
        </div>
        <a class="link-arrow" href="<?= e(url('/blog')) ?>">همه نوشته‌ها <span aria-hidden="true">←</span></a>
      </div>
      <ul class="latest-list">
        <?php foreach ($latest as $item): ?>
        <li>
          <a href="<?= e(url('/blog/' . $item['slug'])) ?>">
            <strong><?= e($item['title']) ?></strong>
            <time datetime="<?= e(date('Y-m-d', (int) strtotime((string) $item['published_at']))) ?>"><?= e(jdate($item['published_at'], 'j F Y')) ?></time>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

  </div>
</article>

<?= json_ld([
    '@type' => 'Article',
    'headline' => $post['title'],
    'description' => excerpt((string) ($post['excerpt'] ?: $post['body']), 200),
    'datePublished' => $post['published_at'] ?? null,
    'dateModified'  => $post['updated_at'] ?? $post['published_at'] ?? null,
    'author' => ['@type' => 'Person', 'name' => $post['author_name'] ?? ($siteName ?? 'نگاه مدیا')],
    'publisher' => ['@type' => 'Organization', 'name' => $siteName ?? 'نگاه مدیا'],
    'mainEntityOfPage' => site_url('/blog/' . $post['slug']),
]) ?>

<?php View::stop(); ?>
