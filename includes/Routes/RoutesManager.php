<?php
namespace App\Routes;

use App\Application;
use App\Controllers\View\AdminController;
use App\Controllers\View\ExtraStuffController;
use App\Controllers\View\IndexController;
use App\Controllers\View\SetupController;
use App\Controllers\Api\InstallController;
use App\Controllers\Api\UpdateController;
use App\Controllers\View\JsController;
use App\Controllers\Api\JsonHttpAdminController;
use App\Controllers\Api\JsonHttpController;
use App\Controllers\View\ServerStuffController;
use App\Controllers\Api\TransferController;
use App\Controllers\Api\UserPasswordResource;
use App\Middlewares\BlockOnInvalidLicense;
use App\Middlewares\IsUpToDate;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAdminAuthentication;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\MiddlewareContract;
use App\Middlewares\RequireAuthorization;
use App\Middlewares\RequireInstalledAndNotUpdated;
use App\Middlewares\RequireNotInstalled;
use App\Middlewares\RequireNotInstalledOrNotUpdated;
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
        $r->addRoute('GET', '/js.php', [
            'uses' => JsController::class . '@get',
        ]);

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
                    'middlewares' => [[RequireAuthorization::class, "manage_users"]],
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

        $r->addRoute("GET", "/setup", [
            'middlewares' => [RequireNotInstalledOrNotUpdated::class],
            'uses' => SetupController::class . "@get",
        ]);

        $r->addRoute("POST", "/api/install", [
            'middlewares' => [RequireNotInstalled::class],
            'uses' => InstallController::class . "@post",
        ]);

        $r->addRoute("POST", "/api/update", [
            'middlewares' => [RequireInstalledAndNotUpdated::class],
            'uses' => UpdateController::class . "@post",
        ]);
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

        foreach ($middlewares as $middlewareData) {
            if (is_array($middlewareData)) {
                $middlewareClass = $middlewareData[0];
                $args = $middlewareData[1];
            } else {
                $middlewareClass = $middlewareData;
                $args = [];
            }

            /** @var MiddlewareContract $middleware */
            $middleware = $this->app->make($middlewareClass);

            $response = $middleware->handle($request, $this->app, $args);
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
