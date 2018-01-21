<?php
namespace Tests;

use App\Kernels\KernelContract;
use App\Kernels\ServersStuffKernel;

class ServerTestCase extends TestCase
{
    use MakesHttpRequests;

    protected function createApplication()
    {
        define('IN_SCRIPT', '1');
        define('SCRIPT_NAME', 'servers_stuff');

        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->singleton(KernelContract::class, ServersStuffKernel::class);
        require __DIR__ . '/../bootstrap/app_global.php';

        return $app;
    }

    protected function prepareUrlForRequest($uri)
    {
        return 'http://example.com/servers_stuff.php' . $uri;
    }
}
