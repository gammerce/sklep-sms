<?php

$app = new App\Application(
    realpath(__DIR__ . '/../')
);

$app->singleton(
    App\Kernels\ConsoleKernelContract::class,
    App\Kernels\ConsoleKernel::class
);

$app->singleton(
    App\ExceptionHandlerContract::class,
    App\ExceptionHandler::class
);

return $app;
