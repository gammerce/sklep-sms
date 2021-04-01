<?php
namespace App\System;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

interface ExceptionHandlerContract
{
    /**
     * @param Exception|Throwable $e
     * @return void
     */
    public function report($e): void;

    /**
     * @param Request $request
     * @param Exception|Throwable $e
     * @return Response
     */
    public function render(Request $request, $e): Response;
}
