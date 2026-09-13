<?php
use App\Core\View;
View::extend('layouts.admin'); ?>
<?php View::start('content'); ?>

<section class="panel">
  <header class="panel-head">
    <h2>کاربران پنل</h2>
    <span class="panel-meta"><?= e(jdigits(count($users))) ?> کاربر</span>
  </header>
  <div class="panel-body panel-body-flush">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:64px"></th>
            <th>نام</th>
            <th>نام کاربری</th>
            <th>ایمیل</th>
            <th style="width:120px">نقش</th>
            <th style="width:160px">آخرین ورود</th>
            <th style="width:110px">وضعیت</th>
            <th style="width:90px"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
          <tr>
            <td><span class="thumb thumb-round" aria-hidden="true"><?= e(mb_substr((string) $user['name'], 0, 1)) ?></span></td>
            <td><strong class="cell-title"><?= e($user['name']) ?></strong></td>
            <td><code dir="ltr"><?= e($user['username']) ?></code></td>
            <td><small dir="ltr"><?= e($user['email'] ?: '—') ?></small></td>
            <td><span class="badge-pill <?= $user['role'] === 'admin' ? 'is-accent' : '' ?>"><?= $user['role'] === 'admin' ? 'مدیر' : 'ویرایشگر' ?></span></td>
            <td><small><?= e($user['last_login_at'] ? \App\Core\Jalali::ago($user['last_login_at']) : '—') ?></small></td>
            <td><span class="status-pill <?= (int) $user['is_active'] === 1 ? 'is-on' : 'is-off' ?>"><?= (int) $user['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?></span></td>
            <td>
              <button class="btn btn-xs btn-ghost" type="button" data-edit-user
                      data-id="<?= (int) $user['id'] ?>"
                      data-name="<?= e($user['name']) ?>"
                      data-email="<?= e((string) $user['email']) ?>"
                      data-role="<?= e($user['role']) ?>"
                      data-active="<?= (int) $user['is_active'] ?>">ویرایش</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<div class="panel-grid">
  <section class="panel">
    <header class="panel-head"><h2>افزودن کاربر جدید</h2></header>
    <div class="panel-body">
      <form action="<?= e(url('/admin/users')) ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-field">
          <label for="name">نام و نام خانوادگی <span aria-hidden="true">*</span></label>
          <input id="name" type="text" name="name" value="<?= e((string) old('name')) ?>" required>
        </div>
        <div class="form-field">
          <label for="username">نام کاربری <span aria-hidden="true">*</span></label>
          <input id="username" type="text" name="username" value="<?= e((string) old('username')) ?>" dir="ltr" required pattern="[a-zA-Z0-9_.]+">
          <small class="field-hint">فقط حروف انگلیسی، عدد، نقطه و زیرخط.</small>
        </div>
        <div class="form-field">
          <label for="email">ایمیل</label>
          <input id="email" type="email" name="email" value="<?= e((string) old('email')) ?>" dir="ltr">
        </div>
        <div class="form-row">
          <div class="form-field">
            <label for="password">رمز عبور <span aria-hidden="true">*</span></label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
          </div>
          <div class="form-field">
            <label for="password_confirmation">تکرار رمز</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
          </div>
        </div>
        <div class="form-field">
          <label for="role">نقش</label>
          <select id="role" name="role">
            <option value="editor">ویرایشگر (بدون دسترسی به کاربران)</option>
            <option value="admin">مدیر (دسترسی کامل)</option>
          </select>
        </div>
        <label class="checkbox-line"><input type="checkbox" name="is_active" value="1" checked><span>حساب فعال</span></label>
        <button class="btn btn-primary btn-block" type="submit">ایجاد کاربر</button>
      </form>
    </div>
  </section>

  <section class="panel">
    <header class="panel-head"><h2>ویرایش کاربر</h2></header>
    <div class="panel-body">
      <form action="#" method="post" data-user-edit-form>
        <?= csrf_field() ?>
        <p class="panel-empty panel-empty-sm">برای ویرایش، روی دکمه «ویرایش» در جدول بالا کلیک کنید.</p>

        <div class="form-field">
          <label for="edit_name">نام و نام خانوادگی</label>
          <input id="edit_name" type="text" name="name" disabled>
        </div>
        <div class="form-field">
          <label for="edit_email">ایمیل</label>
          <input id="edit_email" type="email" name="email" dir="ltr" disabled>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label for="edit_password">رمز عبور جدید</label>
            <input id="edit_password" type="password" name="password" autocomplete="new-password" disabled>
            <small class="field-hint">برای تغییر ندادن، خالی بگذارید.</small>
          </div>
          <div class="form-field">
            <label for="edit_password_confirmation">تکرار رمز</label>
            <input id="edit_password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" disabled>
          </div>
        </div>
        <div class="form-field">
          <label for="edit_role">نقش</label>
          <select id="edit_role" name="role" disabled>
            <option value="editor">ویرایشگر</option>
            <option value="admin">مدیر</option>
          </select>
        </div>
        <label class="checkbox-line"><input type="checkbox" name="is_active" value="1" id="edit_active" disabled><span>حساب فعال</span></label>
        <button class="btn btn-primary btn-block" type="submit" disabled data-user-edit-submit>ذخیره تغییرات</button>
      </form>
    </div>
  </section>
</div>

<?php View::stop(); ?>
