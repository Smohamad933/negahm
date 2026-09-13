<?php
/**
 * لی‌اوت صفحات خطا — بدون نیاز به دیتابیس
 * @var int    $status
 * @var string $message
 * @var string $trace
 * @var string $file
 */
$status  = $status ?? 500;
$message = $message ?? '';
$trace   = $trace ?? '';
$file    = $file ?? '';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(jdigits($status)) ?> — <?= e($message ?: 'خطا') ?></title>
<meta name="robots" content="noindex,follow">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body class="error-body">
<div class="error-wrap">
  <p class="error-code"><?= e(jdigits($status)) ?></p>
  <h1><?= e($message ?: 'خطای غیرمنتظره') ?></h1>

  <?php if ($status === 404): ?>
    <p class="error-hint">ممکن است آدرس را اشتباه نوشته باشید یا این صفحه جابه‌جا شده باشد.</p>
  <?php endif; ?>

  <div class="error-actions">
    <a class="btn btn-primary" href="<?= e(url('/')) ?>">بازگشت به صفحه اصلی</a>
    <a class="btn btn-ghost" href="<?= e(url('/works')) ?>">دیدن نمونه‌کارها</a>
    <a class="btn btn-ghost" href="<?= e(url('/contact')) ?>">تماس با ما</a>
  </div>

  <?php if ($trace !== ''): ?>
  <details class="error-debug">
    <summary>جزئیات فنی (حالت اشکال‌زدایی)</summary>
    <p><code><?= e($file) ?></code></p>
    <pre><?= e($trace) ?></pre>
  </details>
  <?php endif; ?>
</div>
</body>
</html>
