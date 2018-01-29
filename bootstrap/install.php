<?php

$app = new App\Application();

$app->singleton(
    App\ExceptionHandlerContract::class,
    Install\ExceptionHandler::class
);

return $app;
