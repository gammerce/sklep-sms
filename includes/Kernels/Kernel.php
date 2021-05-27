<?php
namespace App\Kernels;

use App\Routing\RoutesManager;
use App\System\Application;
use App\System\ExceptionHandlerContract;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

class Kernel implements KernelContract
{
    private Application $app;
    private RoutesManager $routesManager;

    public function __construct(Application $app, RoutesManager $routesManager)
    {
        $this->app = $app;
        $this->routesManager = $routesManager;
    }

    public function handle(Request $request): Response
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
            // PHP 7.x+ support
            /** @var ExceptionHandlerContract $handler */
            $handler = $this->app->make(ExceptionHandlerContract::class);
            $handler->report($e);
            return $handler->render($request, $e);
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->app->terminate($request, $response);
    }
}
