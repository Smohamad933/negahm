<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<header class="page-actions">
  <form class="inline-search" action="<?= e(url('/admin/messages')) ?>" method="get" role="search">
    <label class="sr-only" for="q">جست‌وجو</label>
    <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="جست‌وجو در پیام‌ها…">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <button type="submit">جست‌وجو</button>
  </form>

  <ul class="tab-row">
    <li><a class="tab <?= $status === '' ? 'is-active' : '' ?>" href="<?= e(url('/admin/messages')) ?>">صندوق ورودی (<?= e(jdigits($counts['inbox'])) ?>)</a></li>
    <li><a class="tab <?= $status === 'unread' ? 'is-active' : '' ?>" href="<?= e(url('/admin/messages?status=unread')) ?>">خوانده‌نشده (<?= e(jdigits($counts['unread'])) ?>)</a></li>
    <li><a class="tab <?= $status === 'read' ? 'is-active' : '' ?>" href="<?= e(url('/admin/messages?status=read')) ?>">خوانده‌شده</a></li>
    <li><a class="tab <?= $status === 'archived' ? 'is-active' : '' ?>" href="<?= e(url('/admin/messages?status=archived')) ?>">بایگانی (<?= e(jdigits($counts['archived'])) ?>)</a></li>
  </ul>
</header>

<section class="panel">
  <div class="panel-body panel-body-flush">
    <?php if ($pager['data'] === []): ?>
      <p class="panel-empty">پیامی در این بخش وجود ندارد.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:40px"></th>
            <th>فرستنده</th>
            <th>تماس</th>
            <th>موضوع</th>
            <th style="width:150px">تاریخ</th>
            <th style="width:190px">عملیات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pager['data'] as $message): ?>
          <tr class="<?= (int) $message['is_read'] === 0 ? 'is-unread' : '' ?>">
            <td><span class="status-dot <?= (int) $message['is_read'] === 0 ? 'is-on' : 'is-off' ?>" title="<?= (int) $message['is_read'] === 0 ? 'خوانده‌نشده' : 'خوانده‌شده' ?>"></span></td>
            <td>
              <strong class="cell-title"><?= e($message['name']) ?></strong>
              <?php if (!empty($message['company'])): ?><small><?= e($message['company']) ?></small><?php endif; ?>
            </td>
            <td>
              <small dir="ltr"><?= e($message['phone']) ?></small>
              <?php if (!empty($message['email'])): ?><small dir="ltr"><?= e($message['email']) ?></small><?php endif; ?>
            </td>
            <td>
              <a class="cell-link" href="<?= e(url('/admin/messages/' . $message['id'])) ?>">
                <?= e($message['subject'] ?: excerpt((string) $message['body'], 55)) ?>
              </a>
              <?php if (!empty($message['service_title'])): ?><small><?= e($message['service_title']) ?></small><?php endif; ?>
            </td>
            <td><small><?= e(\App\Core\Jalali::ago($message['created_at'])) ?></small></td>
            <td>
              <div class="row-actions">
                <a class="btn btn-xs btn-ghost" href="<?= e(url('/admin/messages/' . $message['id'])) ?>">خواندن</a>
                <form action="<?= e(url('/admin/messages/' . $message['id'] . '/archive')) ?>" method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="archived" value="<?= (int) $message['is_archived'] === 1 ? '0' : '1' ?>">
                  <button class="btn btn-xs btn-ghost" type="submit"><?= (int) $message['is_archived'] === 1 ? 'خروج از بایگانی' : 'بایگانی' ?></button>
                </form>
                <form action="<?= e(url('/admin/messages/' . $message['id'] . '/delete')) ?>" method="post" data-confirm="حذف این پیام؟">
                  <?= csrf_field() ?>
                  <button class="btn btn-xs btn-danger" type="submit">حذف</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?= pagination_links($pager, '/admin/messages') ?>
    <?php endif; ?>
  </div>
</section>

<?php View::stop(); ?>
