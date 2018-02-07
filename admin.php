<?php
define('IN_SCRIPT', '1');
define('SCRIPT_NAME', 'admin');

error_reporting(E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_COMPILE_ERROR);
ini_set('display_errors', 1);

session_name('admin');
session_start();

require __DIR__ . '/bootstrap/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->singleton(
    App\Kernels\KernelContract::class,
    App\Kernels\AdminKernel::class
);

/** @var App\Kernels\KernelContract $kernel */
$kernel = $app->make(App\Kernels\KernelContract::class);
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
