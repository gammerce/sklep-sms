<?php
namespace App\Routes;

use App\Application;
use App\Controllers\IndexController;
use App\Controllers\TransferController;
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
            'GET', '/',
            IndexController::class . '@get'
        );

        $r->addRoute(
            'GET', '/index.php',
            IndexController::class . '@get'
        );

        $r->addRoute(
            'GET', '/transfer/{transferService}',
            TransferController::class . '@get'
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
        return $this->handleDispatcherResponse($routeInfo);
    }

    private function handleDispatcherResponse($routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return new Response('', 404);
            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response('', 405);
            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo);
        }
    }

    private function handleFoundRoute($routeInfo)
    {
        return $this->app->call($routeInfo[1], $routeInfo[2]);
    }

    private function createDispatcher()
    {
        return \FastRoute\simpleDispatcher(function (RouteCollector $r) {
            $this->defineRoutes($r);
        });
    }
}