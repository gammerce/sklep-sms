<?php

$app = new App\Application(
    realpath(__DIR__ . '/../')
);

$app->singleton(
    App\ExceptionHandlerContract::class,
    App\ExceptionHandler::class
);

return $app;
