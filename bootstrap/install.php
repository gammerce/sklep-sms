<?php

$app = new App\Application(
    realpath(__DIR__.'/../')
);

$app->singleton(
    App\ExceptionHandlerContract::class,
    Install\ExceptionHandler::class
);

return $app;
