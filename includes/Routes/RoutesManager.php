<?php
namespace App\Routes;

use App\Application;
use App\Controllers\Api\Admin\LogResource;
use App\Controllers\Api\Admin\PageActionBoxResource;
use App\Controllers\Api\Admin\ServiceCodeAddFormController;
use App\Controllers\Api\Admin\ServiceModuleExtraFieldsController;
use App\Controllers\Api\Admin\SettingsController;
use App\Controllers\Api\Admin\UserPasswordResource;
use App\Controllers\Api\Admin\UserResource;
use App\Controllers\Api\Admin\UserServiceAddFormController;
use App\Controllers\Api\Admin\UserServiceCollection;
use App\Controllers\Api\Admin\UserServiceResource as AdminUserServiceResource;
use App\Controllers\Api\Admin\WalletChargeResource;
use App\Controllers\Api\BrickResource;
use App\Controllers\Api\IncomeController;
use App\Controllers\Api\InstallController;
use App\Controllers\Api\JsonHttpAdminController;
use App\Controllers\Api\LogInController;
use App\Controllers\Api\LogOutController;
use App\Controllers\Api\PasswordForgottenController;
use App\Controllers\Api\PasswordResetController;
use App\Controllers\Api\PasswordResource;
use App\Controllers\Api\PaymentResource;
use App\Controllers\Api\PurchaseResource;
use App\Controllers\Api\PurchaseValidationResource;
use App\Controllers\Api\RegisterController;
use App\Controllers\Api\ServiceActionController;
use App\Controllers\Api\ServiceLongDescriptionResource;
use App\Controllers\Api\ServiceTakeOverController;
use App\Controllers\Api\ServiceTakeOverFormController;
use App\Controllers\Api\SessionLanguageResource;
use App\Controllers\Api\TemplateResource;
use App\Controllers\Api\TransferController;
use App\Controllers\Api\UpdateController;
use App\Controllers\Api\UserProfileResource;
use App\Controllers\Api\UserServiceBrickController;
use App\Controllers\Api\UserServiceEditFormController;
use App\Controllers\Api\UserServiceResource;
use App\Controllers\View\AdminController;
use App\Controllers\View\ExtraStuffController;
use App\Controllers\View\IndexController;
use App\Controllers\View\JsController;
use App\Controllers\View\ServerStuffController;
use App\Controllers\View\SetupController;
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
        $r->get('/js.php', [
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

                $r->addRoute(['GET', 'POST'], '/transfer_finalize.php', [
                    'middlewares' => [BlockOnInvalidLicense::class],
                    'uses' => TransferController::class . '@oldAction',
                ]);

                $r->addGroup(
                    [
                        "middlewares" => [BlockOnInvalidLicense::class, UpdateUserActivity::class],
                    ],
                    function (RouteCollector $r) {
                        $r->addRoute(['GET', 'POST'], '/', [
                            'middlewares' => [RunCron::class],
                            'uses' => IndexController::class . '@oldAction',
                        ]);

                        $r->addRoute(['GET', 'POST'], '/page/{pageId}', [
                            'middlewares' => [RunCron::class],
                            'uses' => IndexController::class . '@action',
                        ]);

                        $r->addRoute(['GET', 'POST'], '/index.php', [
                            'middlewares' => [RunCron::class],
                            'uses' => IndexController::class . '@oldAction',
                        ]);

                        $r->post('/api/register', [
                            'uses' => RegisterController::class . '@post',
                        ]);

                        $r->post('/api/login', [
                            'uses' => LogInController::class . '@post',
                        ]);

                        $r->post('/api/logout', [
                            'uses' => LogOutController::class . '@post',
                        ]);

                        $r->put('/api/profile', [
                            "middlewares" => [RequireAuthorization::class],
                            'uses' => UserProfileResource::class . '@put',
                        ]);

                        $r->put('/api/session/language', [
                            'uses' => SessionLanguageResource::class . '@put',
                        ]);

                        $r->post('/api/password/forgotten', [
                            'uses' => PasswordForgottenController::class . '@post',
                        ]);

                        $r->post('/api/password/reset', [
                            'uses' => PasswordResetController::class . '@post',
                        ]);

                        $r->put('/api/password', [
                            "middlewares" => [RequireAuthorization::class],
                            'uses' => PasswordResource::class . '@put',
                        ]);

                        $r->get('/api/templates/{name}', [
                            'uses' => TemplateResource::class . '@get',
                        ]);

                        $r->post('/api/purchase/validation', [
                            'uses' => PurchaseValidationResource::class . '@post',
                        ]);

                        $r->post('/api/payment', [
                            'uses' => PaymentResource::class . '@post',
                        ]);

                        $r->get('/api/bricks/{bricks}', [
                            'uses' => BrickResource::class . '@get',
                        ]);

                        $r->get('/api/purchases/{purchaseId}', [
                            'uses' => PurchaseResource::class . '@get',
                        ]);

                        $r->get('/api/services/{serviceId}/long_description', [
                            'uses' => ServiceLongDescriptionResource::class . '@get',
                        ]);

                        $r->get('/api/user_services/{userServiceId}/edit_form', [
                            'uses' => UserServiceEditFormController::class . '@get',
                        ]);

                        $r->get('/api/user_services/{userServiceId}/brick', [
                            'uses' => UserServiceBrickController::class . '@get',
                        ]);

                        $r->put('/api/user_services/{userServiceId}', [
                            'uses' => UserServiceResource::class . '@put',
                        ]);

                        $r->get('/api/income', [
                            'uses' => IncomeController::class . '@get',
                        ]);

                        $r->post('/api/services/{service}/actions/{action}', [
                            'uses' => ServiceActionController::class . '@post',
                        ]);

                        $r->post('/api/services/{service}/take_over', [
                            'uses' => ServiceTakeOverController::class . '@post',
                        ]);

                        $r->get('/api/services/{service}/take_over/create_form', [
                            'uses' => ServiceTakeOverFormController::class . '@get',
                        ]);
                    }
                );
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

                $r->put('/api/admin/users/{userId}/password', [
                    'middlewares' => [[RequireAuthorization::class, "manage_users"]],
                    'uses' => UserPasswordResource::class . '@put',
                ]);

                $r->put('/api/admin/users/{userId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_users"]],
                    'uses' => UserResource::class . '@put',
                ]);

                $r->delete('/api/admin/users/{userId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_users"]],
                    'uses' => UserResource::class . '@destroy',
                ]);

                $r->get('/api/admin/services/{serviceId}/service_codes/add_form', [
                    'middlewares' => [[RequireAuthorization::class, "manage_service_codes"]],
                    'uses' => ServiceCodeAddFormController::class . '@get',
                ]);

                $r->get('/api/admin/services/{serviceId}/user_services/add_form', [
                    'middlewares' => [[RequireAuthorization::class, "manage_user_services"]],
                    'uses' => UserServiceAddFormController::class . '@get',
                ]);

                $r->post('/api/admin/services/{serviceId}/user_services', [
                    'middlewares' => [[RequireAuthorization::class, "manage_user_services"]],
                    'uses' => UserServiceCollection::class . '@post',
                ]);

                $r->put('/api/admin/user_services/{userServiceId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_user_services"]],
                    'uses' => AdminUserServiceResource::class . '@put',
                ]);

                $r->delete('/api/admin/user_services/{userServiceId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_user_services"]],
                    'uses' => AdminUserServiceResource::class . '@destroy',
                ]);

                $r->get('/api/admin/services/{serviceId}/modules/{moduleId}/extra_fields', [
                    'middlewares' => [[RequireAuthorization::class, "manage_user_services"]],
                    'uses' => ServiceModuleExtraFieldsController::class . '@get',
                ]);

                $r->get('/api/admin/pages/{pageId}/action_boxes/{actionBoxId}', [
                    'middlewares' => [RequireAuthorization::class],
                    'uses' => PageActionBoxResource::class . '@get',
                ]);

                $r->post('/api/admin/users/{userId}/wallet/charge', [
                    'middlewares' => [[RequireAuthorization::class, "manage_users"]],
                    'uses' => WalletChargeResource::class . '@post',
                ]);

                $r->put('/api/admin/settings', [
                    'middlewares' => [[RequireAuthorization::class, "manage_settings"]],
                    'uses' => SettingsController::class . '@put',
                ]);

                $r->delete('/api/admin/logs/{logId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_logs"]],
                    'uses' => LogResource::class . '@destroy',
                ]);

                $r->get('/api/admin/bricks/{bricks}', [
                    'uses' => BrickResource::class . '@get',
                ]);

                $r->get('/api/admin/templates/{name}', [
                    'uses' => TemplateResource::class . '@get',
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

        $r->get("/setup", [
            'middlewares' => [RequireNotInstalledOrNotUpdated::class],
            'uses' => SetupController::class . "@get",
        ]);

        $r->post("/api/install", [
            'middlewares' => [RequireNotInstalled::class],
            'uses' => InstallController::class . "@post",
        ]);

        $r->post("/api/update", [
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
