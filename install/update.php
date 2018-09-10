<?php

define('IN_SCRIPT', '1');
define('SCRIPT_NAME', 'ajax_install_update');

error_reporting(E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_COMPILE_ERROR);
ini_set('display_errors', 1);
@set_time_limit(0);

require __DIR__ . '/../bootstrap/autoload.php';

$app = require __DIR__ . '/../bootstrap/install.php';

$app->singleton(
    App\Kernels\KernelContract::class,
    App\Kernels\InstallUpdateKernel::class
);

/** @var App\Kernels\KernelContract $kernel */
$kernel = $app->make(App\Kernels\KernelContract::class);
$request = captureRequest();

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
