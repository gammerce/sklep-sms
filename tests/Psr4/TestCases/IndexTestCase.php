<?php
namespace Tests\Psr4\TestCases;

use App\Kernels\IndexKernel;
use App\Kernels\KernelContract;

class IndexTestCase extends TestCase
{
    use MakesHttpRequests;

    protected function createApplication()
    {
        if (!defined('IN_SCRIPT')) {
            define('IN_SCRIPT', '1');
        }

        $app = parent::createApplication();
        $app->singleton(KernelContract::class, IndexKernel::class);

        return $app;
    }

    protected function prepareUrlForRequest($uri)
    {
        return 'http://localhost/' . ltrim($uri, "/");
    }
}
