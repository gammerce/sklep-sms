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
use App\View\Blocks\BlockAdminContent;
use App\View\Renders\BlockRenderer;
use App\View\Renders\PageLinkRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController
{
    public function get(
        Auth $auth,
        BlockRenderer $blockRenderer,
        License $license,
        Meta $meta,
        PageLinkRenderer $pageLinkRenderer,
        PageManager $pageManager,
        Request $request,
        ServiceModuleManager $serviceModuleManager,
        Template $template,
        TranslationManager $translationManager,
        UrlGenerator $url,
        WebsiteHeader $websiteHeader,
        $pageId = "home"
    ) {
        $lang = $translationManager->user();

        $page = $pageManager->getAdmin($pageId);

        if (!$page) {
            throw new EntityNotFoundException();
        }

        $user = $auth->user();

        $page->addScripts($request);
        $content = $blockRenderer->render(BlockAdminContent::BLOCK_ID, $request, [$page]);

        $boughtServicesLink = $pageLinkRenderer->renderLink("bought_services", $pageId);
        $groupsLink = $pageLinkRenderer->renderLink("groups", $pageId);
        $incomeLink = $pageLinkRenderer->renderLink("income", $pageId);
        $logsLink = $pageLinkRenderer->renderLink("logs", $pageId);
        $mainLink = $pageLinkRenderer->renderLink("home", $pageId);
        $paymentsLink = $pageLinkRenderer->renderLink("payments", $pageId);
        $playersFlagsLink = $pageLinkRenderer->renderLink("players_flags", $pageId);
        $pricingLink = $pageLinkRenderer->renderLink("pricing", $pageId);
        $promoCodesLink = $pageLinkRenderer->renderLink("promo_codes", $pageId);
        $serversLink = $pageLinkRenderer->renderLink("servers", $pageId);
        $servicesLink = $pageLinkRenderer->renderLink("services", $pageId);
        $settingsLink = $pageLinkRenderer->renderLink("settings", $pageId);
        $smsCodesLink = $pageLinkRenderer->renderLink("sms_codes", $pageId);
        $themeLink = $pageLinkRenderer->renderLink("theme", $pageId);
        $transactionServicesLink = $pageLinkRenderer->renderLink("payment_platforms", $pageId);
        $usersLink = $pageLinkRenderer->renderLink("users", $pageId);

        /** @var ServiceModule $serviceModule */
        $serviceModule = collect($serviceModuleManager->all())->first(
            fn($s) => $s instanceof IServiceUserServiceAdminDisplay
        );
        $userServiceLink = $pageLinkRenderer->renderLink("user_service", $pageId, [
            "subpage" => $serviceModule->getModuleId(),
        ]);

        $header = $template->render("admin/header", [
            "currentPageId" => $page->getId(),
            "langJsPath" => $url->versioned("lang.js", ["language" => $lang->getCurrentLanguage()]),
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
                    "themeLink",
                    "transactionServicesLink",
                    "username",
                    "userServiceLink",
                    "usersLink"
                )
            )
        );
    }
}
