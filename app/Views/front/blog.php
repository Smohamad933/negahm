<?php
use App\Core\View;
View::extend('layouts.front');
$activeCat = $activeCat ?? null;
$tags      = $tags ?? [];
?>
<?php View::start('content'); ?>

<?= partial('partials.page-hero', [
    'title'  => $activeCat ? $activeCat['title'] : 'یادداشت‌های نگاه مدیا',
    'lead'   => 'درباره برندینگ، تبلیغات، طراحی و رشد کسب‌وکار می‌نویسیم؛ کوتاه، کاربردی و بدون شعار.',
    'crumbs' => [
        ['label' => 'خانه', 'url' => url('/')],
        ['label' => 'بلاگ', 'url' => url('/blog')],
    ],
]) ?>

<section class="listing blog-listing">
  <div class="container blog-layout">

    <div class="blog-main">
      <div class="filter-bar">
        <form class="filter-search" action="<?= e(url('/blog')) ?>" method="get" role="search">
          <label class="sr-only" for="blog-q">جست‌وجوی مقاله</label>
          <input id="blog-q" type="search" name="q" value="<?= e($q) ?>" placeholder="جست‌وجو در بلاگ…">
          <button type="submit">جست‌وجو</button>
        </form>
      </div>

      <p class="result-count"><?= e(jdigits($pager['total'])) ?> نوشته یافت شد</p>

      <?php if ($pager['data'] === []): ?>
        <div class="empty-state"><p>نوشته‌ای با این مشخصات پیدا نشد.</p></div>
      <?php else: ?>
        <div class="post-grid post-grid-list">
          <?php foreach ($pager['data'] as $post): ?>
          <a class="post-card" href="<?= e(url('/blog/' . $post['slug'])) ?>" data-reveal>
            <span class="post-media">
              <?php if (!empty($post['cover'])): ?>
                <img src="<?= e(upload_url($post['cover'])) ?>" alt="" loading="lazy">
              <?php endif; ?>
            </span>
            <span class="post-body">
              <?php if (!empty($post['category_title'])): ?>
              <em class="work-tag"><?= e($post['category_title']) ?></em>
              <?php endif; ?>
              <strong><?= e($post['title']) ?></strong>
              <small><?= e(excerpt((string) ($post['excerpt'] ?: $post['body']), 130)) ?></small>
              <span class="post-foot">
                <time datetime="<?= e(date('Y-m-d', (int) strtotime((string) $post['published_at']))) ?>"><?= e(jdate($post['published_at'], 'j F Y')) ?></time>
                <span><?= e(jdigits($post['reading_time'])) ?> دقیقه مطالعه</span>
              </span>
            </span>
          </a>
          <?php endforeach; ?>
        </div>

        <?= pagination_links($pager, '/blog') ?>
      <?php endif; ?>
    </div>

    <aside class="blog-side">
      <?php if (!empty($categories)): ?>
      <div class="side-card">
        <h3>دسته‌بندی‌ها</h3>
        <ul class="side-links">
          <?php foreach ($categories as $category): ?>
          <li>
            <a href="<?= e(url('/blog/category/' . $category['slug'])) ?>"
               <?= $activeCat && (int) $activeCat['id'] === (int) $category['id'] ? 'aria-current="true"' : '' ?>>
              <?= e($category['title']) ?> <small><?= e(jdigits($category['posts_count'] ?? 0)) ?></small>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($tags !== []): ?>
      <div class="side-card">
        <h3>برچسب‌ها</h3>
        <ul class="tag-row">
          <?php foreach ($tags as $tag): ?>
          <li><a href="<?= e(url('/blog?q=' . urlencode($tag))) ?>"><?= e($tag) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div class="side-card side-card-cta">
        <h3>پروژه‌ای در ذهن دارید؟</h3>
        <p>یک گفت‌وگوی کوتاه می‌تواند مسیر برند شما را روشن‌تر کند.</p>
        <a class="btn btn-primary btn-block" href="<?= e(url('/contact')) ?>">درخواست مشاوره</a>
      </div>
    </aside>

  </div>
</section>

<?php View::stop(); ?>
