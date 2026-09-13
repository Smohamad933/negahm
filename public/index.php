<?php

/**
 * نگاه مدیا — نقطه ورود برنامه
 * @see https://negahm.ir
 */

declare(strict_types=1);

define('NEGAHM_START', microtime(true));

$root = dirname(__DIR__);

require $root . '/app/Core/Env.php';
require $root . '/app/Core/Config.php';
require $root . '/app/Core/App.php';

App\Core\App::boot($root);
App\Core\App::run();
