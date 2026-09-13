<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'ورود به پنل') ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="login-body">

<main class="login-wrap">

  <a class="admin-brand login-brand" href="<?= e(url('/')) ?>">
    <span class="brand-mark" aria-hidden="true">N</span>
    <span><strong><?= e((string) setting('site_name', 'نگاه مدیا')) ?></strong><small>پنل مدیریت</small></span>
  </a>

  <div class="flash-zone"><?= flash_render() ?></div>

  <form class="login-card" action="<?= e(url('/admin/login')) ?>" method="post" autocomplete="on">
    <?= csrf_field() ?>

    <h1>ورود به پنل مدیریت</h1>
    <p class="login-hint">برای مدیریت برندها، نمونه‌کارها و محتوای سایت وارد شوید.</p>

    <div class="form-field">
      <label for="username">نام کاربری یا ایمیل</label>
      <input id="username" type="text" name="username" value="<?= e((string) old('username')) ?>" required autofocus autocomplete="username">
    </div>

    <div class="form-field">
      <label for="password">رمز عبور</label>
      <div class="password-wrap">
        <input id="password" type="password" name="password" required autocomplete="current-password">
        <button type="button" class="password-toggle" data-password-toggle aria-label="نمایش رمز">◉</button>
      </div>
    </div>

    <label class="checkbox-line">
      <input type="checkbox" name="remember" value="1">
      <span>مرا به خاطر بسپار</span>
    </label>

    <button class="btn btn-primary btn-block" type="submit">ورود</button>

    <p class="login-foot"><a href="<?= e(url('/')) ?>">← بازگشت به سایت</a></p>
  </form>

</main>

<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
