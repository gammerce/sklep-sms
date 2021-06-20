<?php
namespace App\Http\Controllers\View;

use App\Exceptions\EntityNotFoundException;
use App\Managers\PageManager;
use App\Managers\ServiceModuleManager;
use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\ServiceModule;
use App\Support\Meta;
use App\Theme\Template;
use App\System\Auth;
use App\System\License;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\User\Permission;
use App\View\Blocks\BlockAdminContent;
use App\View\Renders\BlockRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController
{
    private Template $template;
    private Translator $lang;
    private UrlGenerator $url;

    public function __construct(
        TranslationManager $translationManager,
        Template $template,
        UrlGenerator $url
    ) {
        $this->template = $template;
        $this->lang = $translationManager->user();
        $this->url = $url;
    }

    public function get(
        Request $request,
        Auth $auth,
        License $license,
        Template $template,
        TranslationManager $translationManager,
        BlockRenderer $blockRenderer,
        UrlGenerator $url,
        PageManager $pageManager,
        WebsiteHeader $websiteHeader,
        ServiceModuleManager $serviceModuleManager,
        Meta $meta,
        $pageId = "home"
    ) {
        $page = $pageManager->getAdmin($pageId);

        if (!$page) {
            throw new EntityNotFoundException();
        }

        $user = $auth->user();
        $lang = $translationManager->user();

        $page->addScripts($request);
        $content = $blockRenderer->render(BlockAdminContent::BLOCK_ID, $request, [$page]);

        $mainLink = $this->renderLink("home", $pageId, $lang->t("main_page"));

        if ($user->can(Permission::VIEW_PLAYER_FLAGS())) {
            $playersFlagsLink = $this->renderLink("players_flags", $pageId);
        }

        if ($user->can(Permission::VIEW_USER_SERVICES())) {
            /** @var ServiceModule $serviceModule */
            $serviceModule = collect($serviceModuleManager->all())->first(
                fn($s) => $s instanceof IServiceUserServiceAdminDisplay
            );
            $pid = "user_service?subpage=" . urlencode($serviceModule->getModuleId());
            $userServiceLink = $this->renderLink($pid, $pageId, $lang->t("users_services"));
        }

        if ($user->can(Permission::VIEW_INCOME())) {
            $boughtServicesLink = $this->renderLink("bought_services", $pageId);
            $incomeLink = $this->renderLink("income", $pageId);
            $paymentsLink = $this->renderLink("payments", $pageId);
        }

        if ($user->can(Permission::MANAGE_SETTINGS())) {
            $settingsLink = $this->renderLink("settings", $pageId);
            $transactionServicesLink = $this->renderLink("payment_platforms", $pageId);
            $pricingLink = $this->renderLink("pricing", $pageId);
        }

        if ($user->can(Permission::VIEW_USERS())) {
            $usersLink = $this->renderLink("users", $pageId);
        }

        if ($user->can(Permission::VIEW_GROUPS())) {
            $groupsLink = $this->renderLink("groups", $pageId);
        }

        if ($user->can(Permission::VIEW_SERVERS())) {
            $serversLink = $this->renderLink("servers", $pageId);
        }

        if ($user->can(Permission::VIEW_SERVICES())) {
            $servicesLink = $this->renderLink("services", $pageId);
        }

        if ($user->can(Permission::VIEW_SMS_CODES())) {
            $smsCodesLink = $this->renderLink("sms_codes", $pageId);
        }

        if ($user->can(Permission::VIEW_PROMO_CODES())) {
            $promoCodesLink = $this->renderLink("promo_codes", $pageId);
        }

        if ($user->can(Permission::VIEW_LOGS())) {
            $logsLink = $this->renderLink("logs", $pageId);
        }

        $header = $template->render("admin/header", [
            "currentPageId" => $page->getId(),
            "pageTitle" => $page->getTitle($request),
            "scripts" => $websiteHeader->getScripts(),
        ]);
        $currentVersion = $meta->getVersion();
        $logoutAction = $url->to("/admin/login");
        $username = $user->getUsername();

        return new Response(
            $template->render(
                "admin/index",
                compact(
                    "boughtServicesLink",
                    "content",
                    "currentVersion",
                    "groupsLink",
                    "header",
                    "incomeLink",
                    "license",
                    "logoutAction",
                    "logsLink",
                    "mainLink",
                    "paymentsLink",
                    "playersFlagsLink",
                    "pricingLink",
                    "promoCodesLink",
                    "serversLink",
                    "servicesLink",
                    "settingsLink",
                    "smsCodesLink",
                    "transactionServicesLink",
                    "username",
                    "userServiceLink",
                    "usersLink"
                )
            )
        );
    }

    /**
     * @param string $pageId
     * @param string $activePageId
     * @param string|null $name
     * @return string
     */
    private function renderLink($pageId, $activePageId, $name = null): string
    {
        $name = $name ?: $this->lang->t($pageId);
        $path = $this->url->to("/admin/$pageId");
        $isActiveClass = $pageId === $activePageId ? "is-active" : null;
        return $this->template->render("admin/page_link", compact("path", "name", "isActiveClass"));
    }
}
