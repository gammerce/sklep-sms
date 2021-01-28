<?php
namespace Tests\Psr4\Concerns;

use App\Kernels\Kernel;
use App\Kernels\KernelContract;
use App\System\Application;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

trait ApplicationConcern
{
    /**
     * @return Application
     */
    protected function createApplication()
    {
        $app = require __DIR__ . "/../../../bootstrap/app.php";
        $app->singleton(Session::class, fn() => new Session(new MockArraySessionStorage()));
        $app->singleton(KernelContract::class, Kernel::class);
        return $app;
    }

    protected function tearDownApplication(Application $app)
    {
        $app->flush();
        $app = null;
    }
}
