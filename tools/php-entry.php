<?php

/**
 * پل اجرای یک درخواست — فقط توسط tools/serve.mjs صدا زده می‌شود.
 *
 * این فایل بخشی از سایت نیست و در محیط واقعی اجرا نمی‌شود. کارش این است که
 * داده‌ی درخواست را از /tmp/req.json بخواند، ابرمتغیرها را پر کند، state
 * مانده از درخواست قبلی را پاک کند و بعد خودِ برنامه (App::boot + App::run)
 * را اجرا کند. خروجی (body) از طریق بافر خروجی PHP برمی‌گردد و وضعیت و
 * هدرها را خود PHP-WASM برمی‌گرداند.
 */

declare(strict_types=1);

use App\Core\App;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;

$root = '/app';

require_once $root . '/app/Core/Env.php';
require_once $root . '/app/Core/Config.php';
require_once $root . '/app/Core/App.php';

$payload = json_decode((string) file_get_contents('/tmp/req.json'), true);
if (!is_array($payload)) {
    http_response_code(500);
    echo 'bad request payload';

    return;
}

/*
 * مرز درخواست: اگر درخواست قبلی روی همین نمونه اجرا شده، نشستش را می‌بندیم و
 * state استاتیک را پاک می‌کنیم. در درخواست اول هنوز autoload ثبت نشده، پس با
 * class_exists بررسی می‌کنیم — در آن حالت هم چیزی برای پاک‌کردن وجود ندارد.
 */
if (class_exists(Session::class)) {
    Session::close();
    Auth::reset();
    View::flush();
}
header_remove();

$_SERVER = $payload['server'] ?? [];
$_GET    = $payload['get'] ?? [];
$_POST   = $payload['post'] ?? [];
$_COOKIE = $payload['cookie'] ?? [];
$_FILES  = $payload['files'] ?? [];
$_REQUEST = array_replace($_GET, $_POST);

App::boot($root);

// App::run() نمونه‌ی کش‌شده را برمی‌دارد؛ باید برای این درخواست تازه شود.
Request::setInstance(new Request());

App::run();
