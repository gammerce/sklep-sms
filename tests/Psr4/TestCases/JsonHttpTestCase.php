<?php
namespace Tests\Psr4\TestCases;

use App\Kernels\JsonHttpKernel;
use App\Kernels\KernelContract;

class JsonHttpTestCase extends TestCase
{
    use MakesHttpRequests;

    protected function createApplication()
    {
        if (!defined('IN_SCRIPT')) {
            define('IN_SCRIPT', '1');
        }

        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->singleton(KernelContract::class, JsonHttpKernel::class);

        return $app;
    }

    protected function prepareUrlForRequest($uri)
    {
        return 'http://localhost/jsonhttp.php' . $uri;
    }
}
