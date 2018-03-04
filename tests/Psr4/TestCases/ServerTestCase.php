<?php
namespace Tests\Psr4\TestCases;

use App\Kernels\KernelContract;
use App\Kernels\ServersStuffKernel;

class ServerTestCase extends TestCase
{
    use MakesHttpRequests;

    protected function createApplication()
    {
        define('IN_SCRIPT', '1');
        define('SCRIPT_NAME', 'servers_stuff');

        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->singleton(KernelContract::class, ServersStuffKernel::class);

        return $app;
    }

    protected function prepareUrlForRequest($uri)
    {
        return 'http://localhost/servers_stuff.php' . $uri;
    }
}
