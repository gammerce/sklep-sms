<?php
namespace App\Middlewares;

use App\Application;
use Symfony\Component\HttpFoundation\Request;

interface MiddlewareContract
{
    public function handle(Request $request, Application $app);
}