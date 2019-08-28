<?php
namespace App\Routes;

use App\Application;
use App\Controllers\AdminController;
use App\Controllers\ExtraStuffController;
use App\Controllers\IndexController;
use App\Controllers\JsController;
use App\Controllers\JsonHttpAdminController;
use App\Controllers\JsonHttpController;
use App\Controllers\ServerStuffController;
use App\Controllers\TransferController;
use App\Controllers\UserPasswordResource;
use App\Middlewares\BlockOnInvalidLicense;
use App\Middlewares\IsUpToDate;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAdminAuthentication;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\MiddlewareContract;
use App\Middlewares\RunCron;
use App\Middlewares\SetAdminSession;
use App\Middlewares\SetLanguage;
use App\Middlewares\SetUserSession;
use App\Middlewares\UpdateUserActivity;
use App\Middlewares\ValidateLicense;
use FastRoute\Dispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function FastRoute\simpleDispatcher;

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
        $r->addGroup(
            [
                "middlewares" => [
                    SetUserSession::class,
                    IsUpToDate::class,
                    LoadSettings::class,
                    SetLanguage::class,
                    ManageAuthentication::class,
                    ValidateLicense::class,
                ],
            ],
            function (RouteCollector $r) {
                $r->addRoute(['GET', 'POST'], '/', [
                    'middlewares' => [
                        UpdateUserActivity::class,
                        RunCron::class,
                        BlockOnInvalidLicense::class,
                    ],
                    'uses' => IndexController::class . '@oldAction',
                ]);

                $r->addRoute(['GET', 'POST'], '/page/{pageId}', [
                    'middlewares' => [
                        UpdateUserActivity::class,
                        RunCron::class,
                        BlockOnInvalidLicense::class,
                    ],
                    'uses' => IndexController::class . '@action',
                ]);

                $r->addRoute(['GET', 'POST'], '/index.php', [
                    'middlewares' => [
                        UpdateUserActivity::class,
                        RunCron::class,
                        BlockOnInvalidLicense::class,
                    ],
                    'uses' => IndexController::class . '@oldAction',
                ]);

                $r->addRoute(['GET', 'POST'], '/transfer/{transferService}', [
                    'uses' => TransferController::class . '@action',
                ]);

                $r->addRoute('GET', '/js.php', [
                    'uses' => JsController::class . '@get',
                ]);

                $r->addRoute(['GET', 'POST'], '/extra_stuff.php', [
                    'middlewares' => [RunCron::class, BlockOnInvalidLicense::class],
                    'uses' => ExtraStuffController::class . '@action',
                ]);

                $r->addRoute(['GET', 'POST'], '/servers_stuff.php', [
                    'middlewares' => [BlockOnInvalidLicense::class],
                    'uses' => ServerStuffController::class . '@action',
                ]);

                $r->addRoute(['GET', 'POST'], '/jsonhttp.php', [
                    'middlewares' => [BlockOnInvalidLicense::class, UpdateUserActivity::class],
                    'uses' => JsonHttpController::class . '@action',
                ]);

                $r->addRoute(['GET', 'POST'], '/transfer_finalize.php', [
                    'middlewares' => [BlockOnInvalidLicense::class],
                    'uses' => TransferController::class . '@oldAction',
                ]);
            }
        );

        $r->addGroup(
            [
                "middlewares" => [
                    SetAdminSession::class,
                    IsUpToDate::class,
                    LoadSettings::class,
                    SetLanguage::class,
                    ManageAdminAuthentication::class,
                    ValidateLicense::class,
                    UpdateUserActivity::class,
                ],
            ],
            function (RouteCollector $r) {
                $r->addRoute(['GET', 'POST'], '/admin[/{pageId}]', [
                    'middlewares' => [RunCron::class],
                    'uses' => AdminController::class . '@action',
                ]);

                $r->addRoute("PUT", '/admin/users/{userId}/password', [
                    'uses' => UserPasswordResource::class . '@put',
                ]);

                $r->addRoute(['GET', 'POST'], '/admin.php', [
                    'middlewares' => [RunCron::class],
                    'uses' => AdminController::class . '@oldAction',
                ]);

                $r->addRoute(['GET', 'POST'], '/jsonhttp_admin.php', [
                    'uses' => JsonHttpAdminController::class . '@action',
                ]);
            }
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
        return simpleDispatcher(
            function (RouteCollector $r) {
                $this->defineRoutes($r);
            },
            [
                "routeCollector" => RouteCollector::class,
            ]
        );
    }
}
