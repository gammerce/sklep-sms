<?php
namespace App\Routing;

use App\Exceptions\EntityNotFoundException;
use App\Http\Controllers\Api\Admin\AntiSpamQuestionCollection;
use App\Http\Controllers\Api\Admin\AntispamQuestionResource;
use App\Http\Controllers\Api\Admin\GroupCollection;
use App\Http\Controllers\Api\Admin\GroupResource;
use App\Http\Controllers\Api\Admin\LogResource;
use App\Http\Controllers\Api\Admin\PageActionBoxResource;
use App\Http\Controllers\Api\Admin\PaymentModuleAddFormController;
use App\Http\Controllers\Api\Admin\PaymentPlatformCollection;
use App\Http\Controllers\Api\Admin\PaymentPlatformResource;
use App\Http\Controllers\Api\Admin\PriceCollection;
use App\Http\Controllers\Api\Admin\PriceResource;
use App\Http\Controllers\Api\Admin\ServerCollection;
use App\Http\Controllers\Api\Admin\ServerResource;
use App\Http\Controllers\Api\Admin\ServiceCodeAddFormController;
use App\Http\Controllers\Api\Admin\ServiceCodeCollection;
use App\Http\Controllers\Api\Admin\ServiceCodeResource;
use App\Http\Controllers\Api\Admin\ServiceCollection;
use App\Http\Controllers\Api\Admin\ServiceModuleExtraFieldsController;
use App\Http\Controllers\Api\Admin\ServiceResource;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\SmsCodeCollection;
use App\Http\Controllers\Api\Admin\SmsCodeResource;
use App\Http\Controllers\Api\Admin\UserPasswordResource;
use App\Http\Controllers\Api\Admin\UserResource;
use App\Http\Controllers\Api\Admin\UserServiceAddFormController;
use App\Http\Controllers\Api\Admin\UserServiceCollection;
use App\Http\Controllers\Api\Admin\UserServiceResource as AdminUserServiceResource;
use App\Http\Controllers\Api\Admin\WalletChargeCollection;
use App\Http\Controllers\Api\BrickResource;
use App\Http\Controllers\Api\CronController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\InstallController;
use App\Http\Controllers\Api\LogInController;
use App\Http\Controllers\Api\LogOutController;
use App\Http\Controllers\Api\PasswordForgottenController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PasswordResource;
use App\Http\Controllers\Api\PaymentResource;
use App\Http\Controllers\Api\PurchaseResource;
use App\Http\Controllers\Api\PurchaseValidationResource;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\Server\PurchaseResource as ServerPurchaseResource;
use App\Http\Controllers\Api\Server\ServerConfigController;
use App\Http\Controllers\Api\Server\ServiceLongDescriptionController;
use App\Http\Controllers\Api\ServiceActionController;
use App\Http\Controllers\Api\ServiceLongDescriptionResource;
use App\Http\Controllers\Api\ServiceTakeOverController;
use App\Http\Controllers\Api\ServiceTakeOverFormController;
use App\Http\Controllers\Api\SessionLanguageResource;
use App\Http\Controllers\Api\TemplateResource;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\UpdateController;
use App\Http\Controllers\Api\UserProfileResource;
use App\Http\Controllers\Api\UserServiceBrickController;
use App\Http\Controllers\Api\UserServiceEditFormController;
use App\Http\Controllers\Api\UserServiceResource;
use App\Http\Controllers\View\AdminController;
use App\Http\Controllers\View\IndexController;
use App\Http\Controllers\View\LanguageJsController;
use App\Http\Controllers\View\SetupController;
use App\Http\Middlewares\AuthorizeServer;
use App\Http\Middlewares\BlockOnInvalidLicense;
use App\Http\Middlewares\IsUpToDate;
use App\Http\Middlewares\LoadSettings;
use App\Http\Middlewares\ManageAdminAuthentication;
use App\Http\Middlewares\ManageAuthentication;
use App\Http\Middlewares\MiddlewareContract;
use App\Http\Middlewares\RequireAuthorization;
use App\Http\Middlewares\RequireInstalledAndNotUpdated;
use App\Http\Middlewares\RequireNotInstalled;
use App\Http\Middlewares\RequireNotInstalledOrNotUpdated;
use App\Http\Middlewares\RequireUnauthorization;
use App\Http\Middlewares\RunCron;
use App\Http\Middlewares\SetAdminSession;
use App\Http\Middlewares\SetLanguage;
use App\Http\Middlewares\SetUserSession;
use App\Http\Middlewares\UpdateUserActivity;
use App\Http\Middlewares\ValidateLicense;
use App\System\Application;
use FastRoute\Dispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;
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
        /**
         * @deprecated
         */
        $r->get('/admin.php', [
            'uses' => AdminController::class . '@oldGet',
        ]);

        /**
         * @deprecated
         */
        $r->redirectPermanent('/cron.php', '/api/cron');

        /**
         * @deprecated
         */
        $r->redirectPermanent('/cron', '/api/cron');

        $r->get('/lang.js', [
            'middlewares' => [IsUpToDate::class, LoadSettings::class],
            'uses' => LanguageJsController::class . '@get',
        ]);

        $r->get('/api/cron', [
            'middlewares' => [IsUpToDate::class, LoadSettings::class, ValidateLicense::class],
            'uses' => CronController::class . '@get',
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
                $r->addRoute(['GET', 'POST'], '/transfer/{transferPlatform}', [
                    'uses' => TransferController::class . '@action',
                ]);

                /**
                 * @deprecated
                 */
                $r->addRoute(['GET', 'POST'], '/transfer_finalize.php', [
                    'middlewares' => [BlockOnInvalidLicense::class],
                    'uses' => TransferController::class . '@oldAction',
                ]);

                $r->get('/api/server/services/{serviceId}/long_description', [
                    'middlewares' => [BlockOnInvalidLicense::class],
                    'uses' => ServiceLongDescriptionController::class . '@get',
                ]);

                $r->addGroup(
                    [
                        "middlewares" => [BlockOnInvalidLicense::class, AuthorizeServer::class],
                    ],
                    function (RouteCollector $r) {
                        $r->post('/api/server/purchase', [
                            'uses' => ServerPurchaseResource::class . '@post',
                        ]);

                        $r->get('/api/server/config', [
                            'uses' => ServerConfigController::class . '@get',
                        ]);
                    }
                );

                $r->addGroup(
                    [
                        "middlewares" => [BlockOnInvalidLicense::class, UpdateUserActivity::class],
                    ],
                    function (RouteCollector $r) {
                        $r->addRoute(['GET', 'POST'], '/[page/{pageId}]', [
                            'middlewares' => [RunCron::class],
                            'uses' => IndexController::class . '@action',
                        ]);

                        $r->post('/api/register', [
                            'middlewares' => [RequireUnauthorization::class],
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
                            'middlewares' => [RequireUnauthorization::class],
                            'uses' => PasswordForgottenController::class . '@post',
                        ]);

                        $r->post('/api/password/reset', [
                            'middlewares' => [RequireUnauthorization::class],
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
                            'middlewares' => [RequireAuthorization::class],
                            'uses' => UserServiceEditFormController::class . '@get',
                        ]);

                        $r->get('/api/user_services/{userServiceId}/brick', [
                            'middlewares' => [RequireAuthorization::class],
                            'uses' => UserServiceBrickController::class . '@get',
                        ]);

                        $r->put('/api/user_services/{userServiceId}', [
                            "middlewares" => [RequireAuthorization::class],
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
                    'uses' => UserResource::class . '@delete',
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

                $r->post('/api/admin/services/{serviceId}/service_codes', [
                    'middlewares' => [[RequireAuthorization::class, "manage_service_codes"]],
                    'uses' => ServiceCodeCollection::class . '@post',
                ]);

                $r->get('/api/admin/services/{serviceId}/modules/{moduleId}/extra_fields', [
                    'middlewares' => [[RequireAuthorization::class, "manage_user_services"]],
                    'uses' => ServiceModuleExtraFieldsController::class . '@get',
                ]);

                $r->put('/api/admin/user_services/{userServiceId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_user_services"]],
                    'uses' => AdminUserServiceResource::class . '@put',
                ]);

                $r->delete('/api/admin/user_services/{userServiceId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_user_services"]],
                    'uses' => AdminUserServiceResource::class . '@delete',
                ]);

                $r->get('/api/admin/pages/{pageId}/action_boxes/{actionBoxId}', [
                    'middlewares' => [RequireAuthorization::class],
                    'uses' => PageActionBoxResource::class . '@get',
                ]);

                $r->post('/api/admin/users/{userId}/wallet/charge', [
                    'middlewares' => [[RequireAuthorization::class, "manage_users"]],
                    'uses' => WalletChargeCollection::class . '@post',
                ]);

                $r->delete('/api/admin/service_codes/{serviceCodeId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_service_codes"]],
                    'uses' => ServiceCodeResource::class . '@delete',
                ]);

                $r->post('/api/admin/sms_codes', [
                    'middlewares' => [[RequireAuthorization::class, "manage_sms_codes"]],
                    'uses' => SmsCodeCollection::class . '@post',
                ]);

                $r->delete('/api/admin/sms_codes/{smsCodeId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_sms_codes"]],
                    'uses' => SmsCodeResource::class . '@delete',
                ]);

                $r->post('/api/admin/payment_platforms', [
                    'middlewares' => [[RequireAuthorization::class, "manage_settings"]],
                    'uses' => PaymentPlatformCollection::class . '@post',
                ]);

                $r->put('/api/admin/payment_platforms/{paymentPlatformId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_settings"]],
                    'uses' => PaymentPlatformResource::class . '@put',
                ]);

                $r->delete('/api/admin/payment_platforms/{paymentPlatformId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_settings"]],
                    'uses' => PaymentPlatformResource::class . '@delete',
                ]);

                $r->get('/api/admin/payment_modules/{paymentModuleId}/add_form', [
                    'middlewares' => [[RequireAuthorization::class, "manage_settings"]],
                    'uses' => PaymentModuleAddFormController::class . '@get',
                ]);

                $r->put('/api/admin/settings', [
                    'middlewares' => [[RequireAuthorization::class, "manage_settings"]],
                    'uses' => SettingsController::class . '@put',
                ]);

                $r->delete('/api/admin/logs/{logId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_logs"]],
                    'uses' => LogResource::class . '@delete',
                ]);

                $r->post('/api/admin/groups', [
                    'middlewares' => [[RequireAuthorization::class, "manage_groups"]],
                    'uses' => GroupCollection::class . '@post',
                ]);

                $r->put('/api/admin/groups/{groupId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_groups"]],
                    'uses' => GroupResource::class . '@put',
                ]);

                $r->delete('/api/admin/groups/{groupId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_groups"]],
                    'uses' => GroupResource::class . '@delete',
                ]);

                $r->post('/api/admin/antispam_questions', [
                    'middlewares' => [[RequireAuthorization::class, "manage_antispam_questions"]],
                    'uses' => AntiSpamQuestionCollection::class . '@post',
                ]);

                $r->put('/api/admin/antispam_questions/{antispamQuestionId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_antispam_questions"]],
                    'uses' => AntispamQuestionResource::class . '@put',
                ]);

                $r->delete('/api/admin/antispam_questions/{antispamQuestionId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_antispam_questions"]],
                    'uses' => AntispamQuestionResource::class . '@delete',
                ]);

                $r->post('/api/admin/prices', [
                    'middlewares' => [[RequireAuthorization::class, "manage_settings"]],
                    'uses' => PriceCollection::class . '@post',
                ]);

                $r->put('/api/admin/prices/{priceId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_settings"]],
                    'uses' => PriceResource::class . '@put',
                ]);

                $r->delete('/api/admin/prices/{priceId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_settings"]],
                    'uses' => PriceResource::class . '@delete',
                ]);

                $r->post('/api/admin/servers', [
                    'middlewares' => [[RequireAuthorization::class, "manage_servers"]],
                    'uses' => ServerCollection::class . '@post',
                ]);

                $r->put('/api/admin/servers/{serverId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_servers"]],
                    'uses' => ServerResource::class . '@put',
                ]);

                $r->delete('/api/admin/servers/{serverId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_servers"]],
                    'uses' => ServerResource::class . '@delete',
                ]);

                $r->post('/api/admin/services', [
                    'middlewares' => [[RequireAuthorization::class, "manage_services"]],
                    'uses' => ServiceCollection::class . '@post',
                ]);

                $r->put('/api/admin/services/{serviceId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_services"]],
                    'uses' => ServiceResource::class . '@put',
                ]);

                $r->delete('/api/admin/services/{serviceId}', [
                    'middlewares' => [[RequireAuthorization::class, "manage_services"]],
                    'uses' => ServiceResource::class . '@delete',
                ]);

                $r->get('/api/admin/bricks/{bricks}', [
                    'uses' => BrickResource::class . '@get',
                ]);

                $r->get('/api/admin/templates/{name}', [
                    'uses' => TemplateResource::class . '@get',
                ]);

                $r->post('/api/admin/services/{service}/actions/{action}', [
                    'uses' => ServiceActionController::class . '@post',
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
        // TODO If shop is not up to date, then always return Redirect

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new EntityNotFoundException();
            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response('', 405);
            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo, $request);
            default:
                throw new UnexpectedValueException("Unexpected branch");
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
