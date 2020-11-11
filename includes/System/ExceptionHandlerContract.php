<?php
namespace App\System;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

interface ExceptionHandlerContract
{
    /**
     * @param Exception|Throwable $e
     * @return void
     */
    public function report($e);

    /**
     * @param Request $request
     * @param Exception|Throwable $e
     * @return mixed
     */
    public function render(Request $request, $e);
}
