<?php
namespace App\Routes;

use App\Application;
use App\Controllers\IndexController;
use App\Controllers\JsController;
use App\Controllers\TransferController;
use App\Middlewares\MiddlewareContract;
use App\Middlewares\RunCron;
use App\Middlewares\UpdateUserActivity;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RoutesManager
{
    /** @var Application */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    private function defineRoutes(RouteCollector $r)
    {
        $r->addRoute(
            ['GET', 'POST'], '/',
            [
                'middlewares' => [
                    UpdateUserActivity::class,
                    RunCron::class,
                ],
                'uses'        => IndexController::class . '@oldGet',
            ]
        );

        $r->addRoute(
            ['GET', 'POST'], '/index.php',
            [
                'middlewares' => [
                    UpdateUserActivity::class,
                    RunCron::class,
                ],
                'uses'        => IndexController::class . '@oldGet',
            ]
        );

        $r->addRoute(
            'GET', '/js.php',
            [
                'uses' => JsController::class . '@get',
            ]
        );

        $r->addRoute(
            ['GET', 'POST'], '/transfer_finalize.php',
            [
                'uses' => TransferController::class . '@oldAction',
            ]
        );

        $r->addRoute(
            ['GET', 'POST'], '/transfer/{transferService}',
            [
                'uses' => TransferController::class . '@action',
            ]
        );

        $r->addRoute(
            'GET', '/page/{pageId}',
            [
                'uses' => IndexController::class . '@get',
            ]
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request)
    {
        $method = $request->getMethod();
        $uri = '/' . trim($request->getPathInfo(), '/');

        $routeInfo = $this->createDispatcher()->dispatch($method, $uri);
        return $this->handleDispatcherResponse($routeInfo, $request);
    }

    private function handleDispatcherResponse($routeInfo, Request $request)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return new Response('', 404);
            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response('', 405);
            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo, $request);
        }
    }

    private function handleFoundRoute($routeInfo, Request $request)
    {
        /** @var string[] $middlewares */
        $middlewares = array_get($routeInfo[1], 'middlewares', []);
        $uses = $routeInfo[1]['uses'];

        foreach ($middlewares as $middlewareClass) {
            /** @var MiddlewareContract $middleware */
            $middleware = $this->app->make($middlewareClass);

            $response = $middleware->handle($request, $this->app);
            if ($response) {
                return $response;
            }
        }

        return $this->app->call($uses, $routeInfo[2]);
    }

    private function createDispatcher()
    {
        return \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            $this->defineRoutes($r);
        });
    }
}