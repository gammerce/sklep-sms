<?php

$app = new App\System\Application(
    realpath(__DIR__ . '/../')
);

$app->singleton(
    App\Kernels\ConsoleKernelContract::class,
    App\Kernels\ConsoleKernel::class
);

$app->singleton(
    App\System\ExceptionHandlerContract::class,
    App\System\ExceptionHandler::class
);

return $app;
