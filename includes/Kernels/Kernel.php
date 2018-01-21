<?php
namespace App\Kernels;

use App\Application;
use App\CurrentPage;
use App\Heart;
use App\License;
use App\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class Kernel implements KernelContract
{
    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function terminate(Request $request, Response $response)
    {
        //
    }
}
