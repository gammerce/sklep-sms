<?php

$app = new App\Application();

$app->singleton(
    App\ExceptionHandlerContract::class,
    App\ExceptionHandler::class
);

return $app;
