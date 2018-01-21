<?php
namespace Tests;

use App\Application;
use App\Kernels\KernelContract;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\Request;

class TestCase extends BaseTestCase
{
    /** @var Application */
    protected $app;

    protected function setUp()
    {
        if (!$this->app) {
            $this->app = $this->createApplication();
        }
    }

    protected function tearDown()
    {
        if ($this->app) {
            $this->app->flush();
            $this->app = null;
        }
    }

    protected function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }
}
