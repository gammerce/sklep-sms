<?php
namespace App\System;

use Exception;
use Symfony\Component\HttpFoundation\Request;

interface ExceptionHandlerContract
{
    public function report(Exception $e);

    public function render(Request $request, Exception $e);
}
