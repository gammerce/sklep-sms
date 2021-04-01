<?php
namespace App\Kernels;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface KernelContract
{
    /**
     * Handle an incoming HTTP request.
     *
     * @param  Request $request
     * @return Response
     */
    public function handle(Request $request): Response;

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  Request  $request
     * @param  Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void;
}
