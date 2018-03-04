<?php
namespace Tests\Psr4\TestCases;

use App\Kernels\AdminKernel;
use App\Kernels\KernelContract;

class AdminTestCase extends TestCase
{
    use MakesHttpRequests;

    protected function createApplication()
    {
        define('IN_SCRIPT', '1');
        define('SCRIPT_NAME', 'admin');

        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->singleton(KernelContract::class, AdminKernel::class);

        return $app;
    }

    protected function prepareUrlForRequest($uri)
    {
        return 'http://localhost/admin.php' . $uri;
    }
}
