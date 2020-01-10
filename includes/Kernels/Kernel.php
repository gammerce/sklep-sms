<?php
namespace App\Kernels;

use App\Routing\RoutesManager;
use App\System\Application;
use App\System\ExceptionHandlerContract;
use Exception;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

class Kernel implements KernelContract
{
    /** @var Application */
    private $app;

    /** @var RoutesManager */
    private $routesManager;

    public function __construct(Application $app, RoutesManager $routesManager)
    {
        $this->app = $app;
        $this->routesManager = $routesManager;
    }

    public function handle(Request $request)
    {
        try {
            $this->app->instance(Request::class, $request);
            $request->setSession($this->app->make(Session::class));
            return $this->routesManager->dispatch($request);
        } catch (Exception $e) {
            /** @var ExceptionHandlerContract $handler */
            $handler = $this->app->make(ExceptionHandlerContract::class);
            $handler->report($e);
            return $handler->render($request, $e);
        } catch (Throwable $e) {
            /** @var ExceptionHandlerContract $handler */
            $handler = $this->app->make(ExceptionHandlerContract::class);
            $e = new FatalThrowableError($e);
            $handler->report($e);
            return $handler->render($request, $e);
        }
    }

    public function terminate(Request $request, Response $response)
    {
        $this->app->terminate();
    }
}
