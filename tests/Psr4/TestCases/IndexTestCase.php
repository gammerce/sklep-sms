<?php
namespace Tests\Psr4\TestCases;

use App\Kernels\IndexKernel;
use App\Kernels\KernelContract;

class IndexTestCase extends TestCase
{
    use MakesHttpRequests;

    protected function createApplication()
    {
        define('IN_SCRIPT', '1');
        define('SCRIPT_NAME', 'index');

        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->singleton(KernelContract::class, IndexKernel::class);

        return $app;
    }

    protected function prepareUrlForRequest($uri)
    {
        return 'http://localhost/index.php' . $uri;
    }
}
