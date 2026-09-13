<?php
/**
 * آمار بازدید سایت — تعداد بازدید هر صفحه
 */
use App\Core\View;
View::extend('layouts.admin');

$pager   = $pager ?? ['data' => [], 'total' => 0];
$daily   = $daily ?? [];
$summary = $summary ?? [];
$ranges  = $ranges ?? [];
$days    = (int) ($days ?? 30);
$top     = $top ?? [];

$maxDaily = $daily === [] ? 1 : max(1, max($daily));
?>
<?php View::start('content'); ?>

<section class="panel">
  <header class="panel-head">
    <h2>آمار بازدید</h2>
    <div class="panel-tabs">
      <?php foreach ($ranges as $value => $label): ?>
      <a class="panel-tab<?= (int) $value === $days ? ' is-active' : '' ?>"
         href="<?= e(url('/admin/analytics?days=' . $value)) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
  </header>
  <div class="panel-body">
    <div class="stat-cards">
      <div class="stat-card">
        <span class="stat-card-body">
          <small>بازدید امروز</small>
          <strong><?= e(jdigits((string) ($summary['today'] ?? 0))) ?></strong>
        </span>
      </div>
      <div class="stat-card">
        <span class="stat-card-body">
          <small>بازدید در بازهٔ انتخابی</small>
          <strong><?= e(jdigits((string) ($summary['range'] ?? 0))) ?></strong>
        </span>
      </div>
      <div class="stat-card">
        <span class="stat-card-body">
          <small>بازدیدکنندهٔ یکتا</small>
          <strong><?= e(jdigits((string) ($summary['unique'] ?? 0))) ?></strong>
        </span>
      </div>
      <div class="stat-card">
        <span class="stat-card-body">
          <small>مجموع کل</small>
          <strong><?= e(jdigits((string) ($summary['all'] ?? 0))) ?></strong>
        </span>
      </div>
    </div>

    <?php if ($daily !== []): ?>
    <div class="chart-bars" role="img" aria-label="نمودار بازدید روزانه">
      <?php foreach ($daily as $date => $count): ?>
      <div class="chart-bar" title="<?= e(jdigits((string) $count)) ?> بازدید — <?= e(jdate((string) $date)) ?>">
        <span style="height: <?= (int) round(((int) $count / $maxDaily) * 100) ?>%"></span>
        <small><?= e(jdigits(date('m/d', strtotime((string) $date)))) ?></small>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<div class="form-grid">
  <div class="form-main">
    <section class="panel">
      <header class="panel-head">
        <h2>بازدید به تفکیک صفحه</h2>
        <span class="panel-meta"><?= e(jdigits((string) ($pager['total'] ?? 0))) ?> صفحه</span>
      </header>
      <div class="panel-body panel-body-flush">
        <?php if (empty($pager['data'])): ?>
          <p class="panel-empty">
            هنوز بازدیدی ثبت نشده است. آمار از لحظه‌ای که سایت در دسترس قرار بگیرد جمع می‌شود.
          </p>
        <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>صفحه</th>
                <th style="width:110px">بازدید</th>
                <th style="width:140px">آخرین بازدید</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pager['data'] as $row): ?>
              <tr>
                <td>
                  <strong class="cell-title"><?= e((string) $row['title']) ?></strong>
                  <small><?= e((string) $row['path']) ?></small>
                </td>
                <td><span class="badge-pill"><?= e(jdigits((string) $row['visits'])) ?></span></td>
                <td><?= e(jdate((string) $row['last_seen'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="panel-foot">
          <?= pagination_links($pager, '/admin/analytics') ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <aside class="form-side">
    <?php if ($top !== []): ?>
    <section class="panel">
      <header class="panel-head"><h2>پربازدیدترین‌ها</h2></header>
      <div class="panel-body panel-body-flush">
        <ol class="rank-list">
          <?php foreach ($top as $index => $row): ?>
          <li>
            <span class="rank-num"><?= e(jdigits((string) ($index + 1))) ?></span>
            <span>
              <strong><?= e(\App\Models\Visit::titleFor((string) $row['path'])) ?></strong>
              <small><?= e((string) $row['path']) ?></small>
            </span>
            <span class="rank-count"><?= e(jdigits((string) $row['visits'])) ?></span>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </section>
    <?php endif; ?>

    <section class="panel">
      <header class="panel-head"><h2>پاک‌سازی داده‌ها</h2></header>
      <div class="panel-body">
        <p class="panel-hint">
          رکوردهای قدیمی‌تر از بازهٔ تعیین‌شده حذف می‌شوند. آمار تجمیعی بالا فقط از
          داده‌های موجود محاسبه می‌شود.
        </p>
        <form action="<?= e(url('/admin/analytics/prune')) ?>" method="post" data-confirm="رکوردهای قدیمی حذف شوند؟">
          <?= csrf_field() ?>
          <div class="form-field">
            <label for="prune-days">نگهداری تا (روز)</label>
            <input id="prune-days" type="number" name="days" value="180" min="7" max="3650">
          </div>
          <button class="btn btn-danger btn-block" type="submit">پاک‌سازی</button>
        </form>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head"><h2>چطور شمارش می‌شود؟</h2></header>
      <div class="panel-body">
        <p class="panel-hint">
          هر IP برای هر مسیر، حداکثر یک بار در ۳۰ دقیقه شمرده می‌شود تا رفرش‌های پیاپی
          آمار را خراب نکنند. ربات‌ها و صفحات پنل مدیریت شمرده نمی‌شوند.
        </p>
      </div>
    </section>
  </aside>
</div>

<?php View::stop(); ?>
