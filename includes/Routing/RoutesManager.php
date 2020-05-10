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
use App\Http\Controllers\Api\Admin\PromoCodeCollection;
use App\Http\Controllers\Api\Admin\PromoCodeResource;
use App\Http\Controllers\Api\Admin\ServerCollection;
use App\Http\Controllers\Api\Admin\ServerResource;
use App\Http\Controllers\Api\Admin\ServerTokenController;
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
use App\Http\Controllers\Api\Shop\BrickResource;
use App\Http\Controllers\Api\Shop\CronController;
use App\Http\Controllers\Api\Shop\IncomeController;
use App\Http\Controllers\Api\Setup\InstallController;
use App\Http\Controllers\Api\Ipn\DirectBillingController;
use App\Http\Controllers\Api\Ipn\TransferController;
use App\Http\Controllers\Api\Shop\LogInController;
use App\Http\Controllers\Api\Shop\LogOutController;
use App\Http\Controllers\Api\Shop\PasswordForgottenController;
use App\Http\Controllers\Api\Shop\PasswordResetController;
use App\Http\Controllers\Api\Shop\PasswordResource;
use App\Http\Controllers\Api\Shop\PaymentResource;
use App\Http\Controllers\Api\Shop\PurchaseCollection;
use App\Http\Controllers\Api\Shop\PurchaseResource;
use App\Http\Controllers\Api\Shop\RegisterController;
use App\Http\Controllers\Api\Server\PlayerFlagCollection;
use App\Http\Controllers\Api\Server\PurchaseResource as ServerPurchaseResource;
use App\Http\Controllers\Api\Server\ServerConfigController;
use App\Http\Controllers\Api\Server\ServiceLongDescriptionController;
use App\Http\Controllers\Api\Server\UserServiceCollection as ServerUserServiceCollection;
use App\Http\Controllers\Api\Shop\ServiceActionController;
use App\Http\Controllers\Api\Shop\ServiceLongDescriptionResource;
use App\Http\Controllers\Api\Shop\ServiceTakeOverController;
use App\Http\Controllers\Api\Shop\ServiceTakeOverFormController;
use App\Http\Controllers\Api\Shop\SessionLanguageResource;
use App\Http\Controllers\Api\Shop\TemplateResource;
use App\Http\Controllers\Api\Setup\UpdateController;
use App\Http\Controllers\Api\Shop\UserProfileResource;
use App\Http\Controllers\Api\Shop\UserServiceBrickController;
use App\Http\Controllers\Api\Shop\UserServiceEditFormController;
use App\Http\Controllers\Api\Shop\UserServiceResource;
use App\Http\Controllers\View\AdminAuthController;
use App\Http\Controllers\View\AdminController;
use App\Http\Controllers\View\IndexController;
use App\Http\Controllers\View\LanguageJsController;
use App\Http\Controllers\View\SetupController;
use App\Http\Middlewares\AuthorizeServer;
use App\Http\Middlewares\AuthorizeUser;
use App\Http\Middlewares\BlockOnInvalidLicense;
use App\Http\Middlewares\JsonBody;
use App\Http\Middlewares\RequireAuthorized;
use App\Http\Middlewares\RequireInstalledAndNotUpdated;
use App\Http\Middlewares\RequireNotInstalled;
use App\Http\Middlewares\RequireUnauthorized;
use App\Http\Middlewares\RunCron;
use App\Http\Middlewares\SetLanguage;
use App\Http\Middlewares\SetupAvailable;
use App\Http\Middlewares\StartAdminSession;
use App\Http\Middlewares\StartUserSession;
use App\Http\Middlewares\UpdateUserActivity;
use App\Http\Middlewares\ValidateLicense;
use App\Install\ShopState;
use App\System\Application;
use App\System\Settings;
use FastRoute\Dispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;
use function FastRoute\simpleDispatcher;

class RoutesManager
{
    const TYPE_INSTALL = "install";

    /** @var Application */
    private $app;

    /** @var UrlGenerator */
    private $url;

    /** @var ShopState */
    private $shopState;

    /** @var Settings */
    private $settings;

    public function __construct(
        Application $app,
        UrlGenerator $url,
        ShopState $shopState,
        Settings $settings
    ) {
        $this->app = $app;
        $this->url = $url;
        $this->shopState = $shopState;
        $this->settings = $settings;
    }

    private function defineRoutes(RouteCollector $r)
    {
        /**
         * @deprecated
         */
        $r->redirectPermanent('/cron.php', '/api/cron');

        /**
         * @deprecated
         */
        $r->redirectPermanent('/cron', '/api/cron');

        $r->get('/lang.js', [
            'uses' => LanguageJsController::class . '@get',
        ]);

        $r->get('/api/cron', [
            'uses' => CronController::class . '@get',
        ]);

        $r->addGroup(
            [
                "middlewares" => [SetLanguage::class],
            ],
            function (RouteCollector $r) {
                $r->get('/api/server/services/{serviceId}/long_description', [
                    'uses' => ServiceLongDescriptionController::class . '@get',
                ]);

                $r->addGroup(
                    [
                        "middlewares" => [AuthorizeServer::class],
                    ],
                    function (RouteCollector $r) {
                        $r->post('/api/server/purchase', [
                            'middlewares' => [ValidateLicense::class, BlockOnInvalidLicense::class],
                            'uses' => ServerPurchaseResource::class . '@post',
                        ]);

                        $r->get('/api/server/config', [
                            'uses' => ServerConfigController::class . '@get',
                        ]);

                        $r->get('/api/server/players_flags', [
                            'uses' => PlayerFlagCollection::class . '@get',
                        ]);

                        $r->get('/api/server/user_services', [
                            'uses' => ServerUserServiceCollection::class . '@get',
                        ]);
                    }
                );
            }
        );

        $r->addGroup(
            [
                "middlewares" => [
                    StartUserSession::class,
                    SetLanguage::class,
                    AuthorizeUser::class,
                ],
            ],
            function (RouteCollector $r) {
                /**
                 * @deprecated
                 */
                $r->addRoute(['GET', 'POST'], '/transfer/{paymentPlatform}', [
                    'uses' => TransferController::class . '@action',
                ]);

                $r->addRoute(['GET', 'POST'], '/api/ipn/transfer/{paymentPlatform}', [
                    'uses' => TransferController::class . '@action',
                ]);

                $r->addRoute(['GET', 'POST'], '/api/ipn/direct-billing/{paymentPlatform}', [
                    'uses' => DirectBillingController::class . '@action',
                ]);

                /**
                 * @deprecated
                 */
                $r->addRoute(['GET', 'POST'], '/transfer_finalize.php', [
                    'uses' => TransferController::class . '@oldAction',
                ]);

                $r->addGroup(
                    [
                        "middlewares" => [
                            ValidateLicense::class,
                            BlockOnInvalidLicense::class,
                            UpdateUserActivity::class,
                        ],
                    ],
                    function (RouteCollector $r) {
                        $r->addRoute(['GET', 'POST'], '/[page/{pageId}]', [
                            'middlewares' => [RunCron::class],
                            'uses' => IndexController::class . '@action',
                        ]);

                        $r->post('/api/register', [
                            'middlewares' => [RequireUnauthorized::class],
                            'uses' => RegisterController::class . '@post',
                        ]);

                        $r->post('/api/login', [
                            'uses' => LogInController::class . '@post',
                        ]);

                        $r->post('/api/logout', [
                            'uses' => LogOutController::class . '@post',
                        ]);

                        $r->put('/api/profile', [
                            "middlewares" => [RequireAuthorized::class],
                            'uses' => UserProfileResource::class . '@put',
                        ]);

                        $r->put('/api/session/language', [
                            'uses' => SessionLanguageResource::class . '@put',
                        ]);

                        $r->post('/api/password/forgotten', [
                            'middlewares' => [RequireUnauthorized::class],
                            'uses' => PasswordForgottenController::class . '@post',
                        ]);

                        $r->post('/api/password/reset', [
                            'middlewares' => [RequireUnauthorized::class],
                            'uses' => PasswordResetController::class . '@post',
                        ]);

                        $r->put('/api/password', [
                            "middlewares" => [RequireAuthorized::class],
                            'uses' => PasswordResource::class . '@put',
                        ]);

                        $r->get('/api/templates/{name}', [
                            'uses' => TemplateResource::class . '@get',
                        ]);

                        $r->post('/api/purchases', [
                            'uses' => PurchaseCollection::class . '@post',
                        ]);

                        $r->get('/api/purchases/{purchaseId}', [
                            'uses' => PurchaseResource::class . '@get',
                        ]);

                        $r->post('/api/payment/{transactionId}', [
                            'uses' => PaymentResource::class . '@post',
                        ]);

                        $r->get('/api/bricks/{bricks}', [
                            'uses' => BrickResource::class . '@get',
                        ]);

                        $r->get('/api/services/{serviceId}/long_description', [
                            'uses' => ServiceLongDescriptionResource::class . '@get',
                        ]);

                        $r->get('/api/user_services/{userServiceId}/edit_form', [
                            'middlewares' => [RequireAuthorized::class],
                            'uses' => UserServiceEditFormController::class . '@get',
                        ]);

                        $r->get('/api/user_services/{userServiceId}/brick', [
                            'middlewares' => [RequireAuthorized::class],
                            'uses' => UserServiceBrickController::class . '@get',
                        ]);

                        $r->put('/api/user_services/{userServiceId}', [
                            "middlewares" => [RequireAuthorized::class],
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
                "middlewares" => [StartAdminSession::class, SetLanguage::class],
            ],
            function (RouteCollector $r) {
                $r->get('/admin/login', [
                    'uses' => AdminAuthController::class . '@get',
                ]);

                $r->post('/admin/login', [
                    'uses' => AdminAuthController::class . '@post',
                ]);
            }
        );

        $r->addGroup(
            [
                "middlewares" => [
                    StartAdminSession::class,
                    SetLanguage::class,
                    AuthorizeUser::class,
                    [RequireAuthorized::class, "acp"],
                    ValidateLicense::class,
                    UpdateUserActivity::class,
                ],
            ],
            function (RouteCollector $r) {
                $r->get('/admin[/{pageId}]', [
                    'middlewares' => [RunCron::class],
                    'uses' => AdminController::class . '@get',
                ]);

                $r->put('/api/admin/users/{userId}/password', [
                    'middlewares' => [[RequireAuthorized::class, "manage_users"]],
                    'uses' => UserPasswordResource::class . '@put',
                ]);

                $r->put('/api/admin/users/{userId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_users"]],
                    'uses' => UserResource::class . '@put',
                ]);

                $r->delete('/api/admin/users/{userId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_users"]],
                    'uses' => UserResource::class . '@delete',
                ]);

                $r->get('/api/admin/services/{serviceId}/user_services/add_form', [
                    'middlewares' => [[RequireAuthorized::class, "manage_user_services"]],
                    'uses' => UserServiceAddFormController::class . '@get',
                ]);

                $r->post('/api/admin/services/{serviceId}/user_services', [
                    'middlewares' => [[RequireAuthorized::class, "manage_user_services"]],
                    'uses' => UserServiceCollection::class . '@post',
                ]);

                $r->post('/api/admin/promo_codes', [
                    'middlewares' => [[RequireAuthorized::class, "manage_promo_codes"]],
                    'uses' => PromoCodeCollection::class . '@post',
                ]);

                $r->get('/api/admin/services/{serviceId}/modules/{moduleId}/extra_fields', [
                    'middlewares' => [[RequireAuthorized::class, "manage_user_services"]],
                    'uses' => ServiceModuleExtraFieldsController::class . '@get',
                ]);

                $r->put('/api/admin/user_services/{userServiceId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_user_services"]],
                    'uses' => AdminUserServiceResource::class . '@put',
                ]);

                $r->delete('/api/admin/user_services/{userServiceId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_user_services"]],
                    'uses' => AdminUserServiceResource::class . '@delete',
                ]);

                $r->get('/api/admin/pages/{pageId}/action_boxes/{actionBoxId}', [
                    'middlewares' => [RequireAuthorized::class],
                    'uses' => PageActionBoxResource::class . '@get',
                ]);

                $r->post('/api/admin/users/{userId}/wallet/charge', [
                    'middlewares' => [[RequireAuthorized::class, "manage_users"]],
                    'uses' => WalletChargeCollection::class . '@post',
                ]);

                $r->delete('/api/admin/promo_code/{promoCodeId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_promo_codes"]],
                    'uses' => PromoCodeResource::class . '@delete',
                ]);

                $r->post('/api/admin/sms_codes', [
                    'middlewares' => [[RequireAuthorized::class, "manage_sms_codes"]],
                    'uses' => SmsCodeCollection::class . '@post',
                ]);

                $r->delete('/api/admin/sms_codes/{smsCodeId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_sms_codes"]],
                    'uses' => SmsCodeResource::class . '@delete',
                ]);

                $r->post('/api/admin/payment_platforms', [
                    'middlewares' => [[RequireAuthorized::class, "manage_settings"]],
                    'uses' => PaymentPlatformCollection::class . '@post',
                ]);

                $r->put('/api/admin/payment_platforms/{paymentPlatformId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_settings"]],
                    'uses' => PaymentPlatformResource::class . '@put',
                ]);

                $r->delete('/api/admin/payment_platforms/{paymentPlatformId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_settings"]],
                    'uses' => PaymentPlatformResource::class . '@delete',
                ]);

                $r->get('/api/admin/payment_modules/{paymentModuleId}/add_form', [
                    'middlewares' => [[RequireAuthorized::class, "manage_settings"]],
                    'uses' => PaymentModuleAddFormController::class . '@get',
                ]);

                $r->put('/api/admin/settings', [
                    'middlewares' => [[RequireAuthorized::class, "manage_settings"]],
                    'uses' => SettingsController::class . '@put',
                ]);

                $r->delete('/api/admin/logs/{logId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_logs"]],
                    'uses' => LogResource::class . '@delete',
                ]);

                $r->post('/api/admin/groups', [
                    'middlewares' => [[RequireAuthorized::class, "manage_groups"]],
                    'uses' => GroupCollection::class . '@post',
                ]);

                $r->put('/api/admin/groups/{groupId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_groups"]],
                    'uses' => GroupResource::class . '@put',
                ]);

                $r->delete('/api/admin/groups/{groupId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_groups"]],
                    'uses' => GroupResource::class . '@delete',
                ]);

                $r->post('/api/admin/antispam_questions', [
                    'middlewares' => [[RequireAuthorized::class, "manage_antispam_questions"]],
                    'uses' => AntiSpamQuestionCollection::class . '@post',
                ]);

                $r->put('/api/admin/antispam_questions/{antispamQuestionId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_antispam_questions"]],
                    'uses' => AntispamQuestionResource::class . '@put',
                ]);

                $r->delete('/api/admin/antispam_questions/{antispamQuestionId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_antispam_questions"]],
                    'uses' => AntispamQuestionResource::class . '@delete',
                ]);

                $r->post('/api/admin/prices', [
                    'middlewares' => [[RequireAuthorized::class, "manage_settings"]],
                    'uses' => PriceCollection::class . '@post',
                ]);

                $r->put('/api/admin/prices/{priceId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_settings"]],
                    'uses' => PriceResource::class . '@put',
                ]);

                $r->delete('/api/admin/prices/{priceId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_settings"]],
                    'uses' => PriceResource::class . '@delete',
                ]);

                $r->post('/api/admin/servers', [
                    'middlewares' => [[RequireAuthorized::class, "manage_servers"]],
                    'uses' => ServerCollection::class . '@post',
                ]);

                $r->put('/api/admin/servers/{serverId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_servers"]],
                    'uses' => ServerResource::class . '@put',
                ]);

                $r->delete('/api/admin/servers/{serverId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_servers"]],
                    'uses' => ServerResource::class . '@delete',
                ]);

                $r->post('/api/admin/servers/{serverId}/token', [
                    'middlewares' => [[RequireAuthorized::class, "manage_servers"]],
                    'uses' => ServerTokenController::class . '@post',
                ]);

                $r->post('/api/admin/services', [
                    'middlewares' => [[RequireAuthorized::class, "manage_services"]],
                    'uses' => ServiceCollection::class . '@post',
                ]);

                $r->put('/api/admin/services/{serviceId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_services"]],
                    'uses' => ServiceResource::class . '@put',
                ]);

                $r->delete('/api/admin/services/{serviceId}', [
                    'middlewares' => [[RequireAuthorized::class, "manage_services"]],
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

        $r->addGroup(
            [
                'type' => RoutesManager::TYPE_INSTALL,
            ],
            function (RouteCollector $r) {
                $r->get("/setup", [
                    'middlewares' => [SetupAvailable::class],
                    'uses' => SetupController::class . "@get",
                ]);

                $r->post("/api/install", [
                    'middlewares' => [SetupAvailable::class, RequireNotInstalled::class],
                    'uses' => InstallController::class . "@post",
                ]);

                $r->post("/api/update", [
                    'middlewares' => [SetupAvailable::class, RequireInstalledAndNotUpdated::class],
                    'uses' => UpdateController::class . "@post",
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
        if ($this->shouldRedirectToSetup($routeInfo)) {
            return new RedirectResponse($this->url->to('/setup'));
        }

        $this->settings->load();

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
        $middlewares = array_merge([JsonBody::class], array_get($routeInfo[1], 'middlewares', []));
        $controllerMethod = $routeInfo[1]['uses'];

        return (new Pipeline($this->app))
            ->send($request)
            ->through($middlewares)
            ->then(function () use ($controllerMethod, $routeInfo) {
                return $this->app->call($controllerMethod, $routeInfo[2]);
            });
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

    private function shouldRedirectToSetup(array $routeInfo)
    {
        return array_get(array_get($routeInfo, 1), "type") !== RoutesManager::TYPE_INSTALL &&
            $this->shopState->requiresAction();
    }
}
