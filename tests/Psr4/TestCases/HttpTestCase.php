<?php
namespace Tests\Psr4\TestCases;

use App\Kernels\Kernel;
use App\System\Auth;
use App\Kernels\KernelContract;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class HttpTestCase extends TestCase
{
    use MakesHttpRequests;

    protected function createApplication()
    {
        if (!defined('IN_SCRIPT')) {
            define('IN_SCRIPT', '1');
        }

        $app = parent::createApplication();
        $app->singleton(KernelContract::class, Kernel::class);

        return $app;
    }

    protected function prepareUrlForRequest($uri)
    {
        return 'http://localhost/' . ltrim($uri, "/");
    }

    protected function actAs(User $user)
    {
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $auth->setUser($user);
    }

    protected function decodeJsonResponse(Response $response)
    {
        return json_decode($response->getContent(), true);
    }
}
