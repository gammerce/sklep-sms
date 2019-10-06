<?php
namespace App\Kernels;

use App\Application;
use App\ExceptionHandlerContract;
use App\Middlewares\MiddlewareContract;
use Exception;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

abstract class Kernel implements KernelContract
{
    /** @var Application */
    protected $app;

    /** @var array */
    protected $middlewares = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $request)
    {
        try {
            $this->app->instance(Request::class, $request);
            $request->setSession($this->app->make(Session::class));
            $response = $this->runMiddlewares($request);

            if ($response) {
                return $response;
            }

            return $this->run($request);
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

    abstract protected function run(Request $request);

    public function terminate(Request $request, Response $response)
    {
        $this->app->terminate();
    }

    protected function runMiddlewares(Request $request)
    {
        foreach ($this->middlewares as $middlewareClass) {
            /** @var MiddlewareContract $middleware */
            $middleware = $this->app->make($middlewareClass);

            $response = $middleware->handle($request, $this->app, null);

            if ($response) {
                return $response;
            }
        }

        return null;
    }
}
