<?php
define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "index");

error_reporting(E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_COMPILE_ERROR);
ini_set('display_errors', 1);

session_name('user');
session_start();

require __DIR__ . '/bootstrap/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->singleton(
    App\Kernels\KernelContract::class,
    App\Kernels\IndexKernel::class
);

/** @var App\Kernels\KernelContract $kernel */
$kernel = $app->make(App\Kernels\KernelContract::class);
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
$app->instance(Symfony\Component\HttpFoundation\Request::class, $request);

try {
    require __DIR__ . '/bootstrap/app_global.php';
    $response = $kernel->handle($request);
} catch (Exception $e) {
    /** @var App\ExceptionHandlerContract $handler */
    $handler = $app->make(App\ExceptionHandlerContract::class);
    $handler->report($e);
    $response = $handler->render($request, $e);
} catch (Throwable $e) {
    /** @var App\ExceptionHandlerContract $handler */
    $handler = $app->make(App\ExceptionHandlerContract::class);
    $e = new Symfony\Component\Debug\Exception\FatalThrowableError($e);
    $handler->report($e);
    $response = $handler->render($request, $e);
}

$response->send();
$kernel->terminate($request, $response);
