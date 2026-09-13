<?php

/**
 * نگاه مدیا — نصب‌کننده تحت وب
 *
 * این فایل را در مرورگر باز کنید:  https://yourdomain.com/install/
 * پس از پایان نصب، حتماً پوشه install را حذف کنید (یا این فایل را پاک کنید).
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);

require $root . '/app/Core/Env.php';
require $root . '/app/Core/Config.php';

use App\Core\Env;

Env::load($root . '/.env');

$envPath    = $root . '/.env';
$installed  = is_file($root . '/storage/installed.lock');
$step       = (string) ($_GET['step'] ?? ($installed ? 'done' : '1'));
$errors     = [];
$messages   = [];

$defaults = [
    'DB_HOST'     => Env::get('DB_HOST', 'localhost'),
    'DB_PORT'     => Env::get('DB_PORT', '3306'),
    'DB_DATABASE' => Env::get('DB_DATABASE', 'negahm'),
    'DB_USERNAME' => Env::get('DB_USERNAME', 'root'),
    'DB_PASSWORD' => Env::get('DB_PASSWORD', ''),
    'APP_URL'     => Env::get('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost')),
    'ADMIN_NAME'  => 'مدیر نگاه مدیا',
    'ADMIN_USER'  => 'admin',
    'ADMIN_EMAIL' => 'info@negahm.ir',
    'ADMIN_PASS'  => '',
];

/* ------------------------------------------------ بررسی پیش‌نیازها */
$checks = [
    'نسخه PHP حداقل ۸.۰'            => PHP_VERSION_ID >= 80000,
    'افزونه PDO'                    => extension_loaded('pdo'),
    'افزونه pdo_mysql'              => extension_loaded('pdo_mysql'),
    'افزونه mbstring'               => extension_loaded('mbstring'),
    'افزونه json'                   => extension_loaded('json'),
    'افزونه fileinfo'               => extension_loaded('fileinfo'),
    'پوشه public/uploads نوشتنی'    => is_writable($root . '/public/uploads') || @mkdir($root . '/public/uploads', 0775, true),
    'پوشه storage/logs نوشتنی'      => is_writable($root . '/storage/logs') || @mkdir($root . '/storage/logs', 0775, true),
    'پوشه ریشه برای ساخت .env'      => is_writable($root),
];

/* ------------------------------------------------------ مرحله ۲: نصب */
if ($step === '2' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($defaults as $key => $value) {
        $posted = $_POST[$key] ?? null;
        if ($posted !== null) {
            $defaults[$key] = is_string($posted) ? trim($posted) : $posted;
        }
    }

    if ($defaults['DB_DATABASE'] === '') {
        $errors[] = 'نام پایگاه داده الزامی است.';
    }
    if ($defaults['DB_USERNAME'] === '') {
        $errors[] = 'نام کاربری پایگاه داده الزامی است.';
    }
    if (strlen((string) $defaults['ADMIN_PASS']) < 8) {
        $errors[] = 'رمز عبور مدیر باید حداقل ۸ نویسه باشد.';
    }
    if (!preg_match('/^[a-zA-Z0-9_.]{3,60}$/', (string) $defaults['ADMIN_USER'])) {
        $errors[] = 'نام کاربری مدیر فقط می‌تواند شامل حروف انگلیسی، عدد، نقطه و زیرخط باشد.';
    }

    $pdo = null;
    if ($errors === []) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;charset=utf8mb4',
                $defaults['DB_HOST'],
                (int) $defaults['DB_PORT']
            );
            $pdo = new PDO($dsn, (string) $defaults['DB_USERNAME'], (string) $defaults['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                preg_replace('/[^a-zA-Z0-9_]/', '', (string) $defaults['DB_DATABASE'])
            ));
            $pdo->exec('USE ' . preg_replace('/[^a-zA-Z0-9_]/', '', (string) $defaults['DB_DATABASE']));
            $messages[] = 'اتصال به پایگاه داده برقرار شد.';
        } catch (Throwable $e) {
            $errors[] = 'اتصال به پایگاه داده ناموفق بود: ' . $e->getMessage();
        }
    }

    if ($errors === [] && $pdo instanceof PDO) {
        $schema = @file_get_contents($root . '/database/schema.sql');
        $seed   = @file_get_contents($root . '/database/seed.sql');

        if ($schema === false) {
            $errors[] = 'فایل database/schema.sql پیدا نشد.';
        } else {
            try {
                foreach (splitSql((string) $schema) as $statement) {
                    $pdo->exec($statement);
                }
                $messages[] = 'جدول‌ها با موفقیت ساخته شدند.';
            } catch (Throwable $e) {
                $errors[] = 'ساخت جدول‌ها ناموفق بود: ' . $e->getMessage();
            }
        }

        if ($errors === [] && $seed !== false) {
            try {
                foreach (splitSql((string) $seed) as $statement) {
                    $pdo->exec($statement);
                }
                $messages[] = 'داده‌های اولیه وارد شدند.';
            } catch (Throwable $e) {
                $messages[] = 'هشدار: بخشی از داده‌های اولیه وارد نشد — ' . $e->getMessage();
            }
        }
    }

    if ($errors === [] && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$defaults['ADMIN_USER']]);
            $hash = password_hash((string) $defaults['ADMIN_PASS'], PASSWORD_DEFAULT);

            if ($stmt->fetch() === false) {
                $pdo->prepare('INSERT INTO users (name, username, email, password, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())')
                    ->execute([$defaults['ADMIN_NAME'], $defaults['ADMIN_USER'], $defaults['ADMIN_EMAIL'], $hash, 'admin']);
            } else {
                $pdo->prepare('UPDATE users SET password = ?, name = ?, email = ?, role = ? WHERE username = ?')
                    ->execute([$hash, $defaults['ADMIN_NAME'], $defaults['ADMIN_EMAIL'], 'admin', $defaults['ADMIN_USER']]);
            }
            $messages[] = 'حساب کاربری مدیر تنظیم شد.';
        } catch (Throwable $e) {
            $errors[] = 'ساخت حساب مدیر ناموفق بود: ' . $e->getMessage();
        }
    }

    if ($errors === []) {
        $envContent = buildEnv($defaults);
        if (@file_put_contents($envPath, $envContent) === false) {
            $errors[] = 'نوشتن فایل .env ممکن نشد. دسترسی پوشه ریشه را بررسی کنید.';
        } else {
            @chmod($envPath, 0640);
            $messages[] = 'فایل .env ساخته شد.';
        }
    }

    if ($errors === []) {
        @file_put_contents($root . '/storage/installed.lock', date('Y-m-d H:i:s'));
        $step = 'done';
    } else {
        $step = '1';
    }
}

function splitSql(string $sql): array
{
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $statements = array_filter(array_map('trim', explode(';', $sql)), static fn ($s) => $s !== '');
    return array_values($statements);
}

/** @param array<string,mixed> $data */
function buildEnv(array $data): string
{
    $lines = [
        '# فایل پیکربندی نگاه مدیا — ساخته‌شده توسط نصب‌کننده در ' . date('Y-m-d H:i:s'),
        'APP_NAME="نگاه مدیا"',
        'APP_ENV=production',
        'APP_DEBUG=false',
        'APP_URL="' . rtrim((string) $data['APP_URL'], '/') . '"',
        'APP_TIMEZONE=Asia/Tehran',
        'APP_LOCALE=fa',
        'APP_BASE_PATH=',
        'APP_KEY=' . bin2hex(random_bytes(32)),
        '',
        'DB_DRIVER=mysql',
        'DB_HOST=' . $data['DB_HOST'],
        'DB_PORT=' . $data['DB_PORT'],
        'DB_DATABASE=' . $data['DB_DATABASE'],
        'DB_USERNAME=' . $data['DB_USERNAME'],
        'DB_PASSWORD=' . $data['DB_PASSWORD'],
        'DB_CHARSET=utf8mb4',
        '',
        'UPLOAD_MAX_SIZE=8388608',
        'ALLOWED_IMAGE_TYPES=jpg,jpeg,png,webp,gif,svg',
        'ALLOWED_FILE_TYPES=pdf,zip,mp4,webm,woff2,woff,ttf,otf',
        '',
        'MAIL_FROM="' . $data['ADMIN_EMAIL'] . '"',
        'MAIL_FROM_NAME="نگاه مدیا"',
        'MAIL_NOTIFY="' . $data['ADMIN_EMAIL'] . '"',
        '',
    ];
    return implode("\n", $lines) . "\n";
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>نصب نگاه مدیا</title>
<meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
<link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body class="login-body">
<main class="login-wrap" style="width:min(100%,640px)">

  <div class="login-brand" style="display:flex;align-items:center;gap:.65rem;margin-bottom:1.4rem">
    <span class="brand-mark">N</span>
    <span><strong style="display:block">نگاه مدیا</strong><small style="display:block;color:#6f6b64;font-size:.75rem">نصب‌کننده سایت</small></span>
  </div>

  <?php if ($errors !== []): ?>
    <?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><span class="alert-icon">✦</span><div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div></div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($step === 'done'): ?>
  <div class="login-card">
    <h1>نصب با موفقیت انجام شد ✅</h1>
    <?php foreach ($messages as $message): ?>
      <div class="alert alert-success"><span class="alert-icon">✦</span><div><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div></div>
    <?php endforeach; ?>
    <p class="login-hint">برای امنیت بیشتر، پوشه <code>install</code> را حذف کنید.</p>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <a class="btn btn-primary" href="../">مشاهده سایت</a>
      <a class="btn btn-ghost" href="../admin">ورود به پنل مدیریت</a>
    </div>
  </div>

  <?php else: ?>
  <div class="login-card">
    <h1>پیش‌نیازها</h1>
    <p class="login-hint">پیش از نصب، موارد زیر باید سبز باشند.</p>

    <ul style="margin-bottom:1.4rem">
      <?php foreach ($checks as $label => $ok): ?>
      <li style="display:flex;gap:.6rem;padding:.35rem 0;border-bottom:1px solid rgba(255,255,255,.08);font-size:.9rem">
        <span style="color:<?= $ok ? '#7ee787' : '#ff6b5e' ?>"><?= $ok ? '✓' : '✕' ?></span>
        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
      </li>
      <?php endforeach; ?>
    </ul>

    <?php if (!in_array(false, $checks, true)): ?>
    <form method="post" action="?step=2">
      <h2 style="font-size:1rem;margin-bottom:1rem">اطلاعات پایگاه داده</h2>

      <div class="form-row">
        <div class="form-field">
          <label for="DB_HOST">میزبان</label>
          <input id="DB_HOST" type="text" name="DB_HOST" value="<?= htmlspecialchars((string) $defaults['DB_HOST'], ENT_QUOTES, 'UTF-8') ?>" dir="ltr" required>
        </div>
        <div class="form-field">
          <label for="DB_PORT">پورت</label>
          <input id="DB_PORT" type="text" name="DB_PORT" value="<?= htmlspecialchars((string) $defaults['DB_PORT'], ENT_QUOTES, 'UTF-8') ?>" dir="ltr" required>
        </div>
      </div>

      <div class="form-field">
        <label for="DB_DATABASE">نام پایگاه داده</label>
        <input id="DB_DATABASE" type="text" name="DB_DATABASE" value="<?= htmlspecialchars((string) $defaults['DB_DATABASE'], ENT_QUOTES, 'UTF-8') ?>" dir="ltr" required>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label for="DB_USERNAME">نام کاربری</label>
          <input id="DB_USERNAME" type="text" name="DB_USERNAME" value="<?= htmlspecialchars((string) $defaults['DB_USERNAME'], ENT_QUOTES, 'UTF-8') ?>" dir="ltr" required>
        </div>
        <div class="form-field">
          <label for="DB_PASSWORD">رمز عبور</label>
          <input id="DB_PASSWORD" type="password" name="DB_PASSWORD" value="<?= htmlspecialchars((string) $defaults['DB_PASSWORD'], ENT_QUOTES, 'UTF-8') ?>" dir="ltr">
        </div>
      </div>

      <h2 style="font-size:1rem;margin:1.4rem 0 1rem">حساب مدیر</h2>

      <div class="form-row">
        <div class="form-field">
          <label for="ADMIN_NAME">نام</label>
          <input id="ADMIN_NAME" type="text" name="ADMIN_NAME" value="<?= htmlspecialchars((string) $defaults['ADMIN_NAME'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-field">
          <label for="ADMIN_USER">نام کاربری</label>
          <input id="ADMIN_USER" type="text" name="ADMIN_USER" value="<?= htmlspecialchars((string) $defaults['ADMIN_USER'], ENT_QUOTES, 'UTF-8') ?>" dir="ltr" required>
        </div>
      </div>

      <div class="form-field">
        <label for="ADMIN_EMAIL">ایمیل</label>
        <input id="ADMIN_EMAIL" type="email" name="ADMIN_EMAIL" value="<?= htmlspecialchars((string) $defaults['ADMIN_EMAIL'], ENT_QUOTES, 'UTF-8') ?>" dir="ltr">
      </div>

      <div class="form-field">
        <label for="ADMIN_PASS">رمز عبور <span aria-hidden="true">*</span></label>
        <input id="ADMIN_PASS" type="password" name="ADMIN_PASS" required minlength="8" autocomplete="new-password">
        <small class="field-hint">حداقل ۸ نویسه. پس از نصب حتماً آن را تغییر دهید.</small>
      </div>

      <div class="form-field">
        <label for="APP_URL">آدرس سایت</label>
        <input id="APP_URL" type="text" name="APP_URL" value="<?= htmlspecialchars((string) $defaults['APP_URL'], ENT_QUOTES, 'UTF-8') ?>" dir="ltr">
      </div>

      <button class="btn btn-primary btn-block" type="submit">شروع نصب</button>
    </form>
    <?php else: ?>
      <div class="alert alert-warning"><span class="alert-icon">✦</span><div>ابتدا موارد قرمز را برطرف کنید، سپس این صفحه را تازه کنید.</div></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</main>
</body>
</html>
