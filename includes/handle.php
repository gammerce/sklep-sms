<?php

use App\Exceptions\RequireInstallationException;
use App\ShopState;

/** @var App\Kernels\KernelContract $kernel */
$kernel = $app->make(App\Kernels\KernelContract::class);
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
$app->instance(Symfony\Component\HttpFoundation\Request::class, $request);

try {
    require __DIR__ . '/../bootstrap/app_global.php';

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
