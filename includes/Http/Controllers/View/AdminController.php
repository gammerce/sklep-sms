<?php
namespace App\Http\Controllers\View;

use App\Exceptions\EntityNotFoundException;
use App\Managers\PageManager;
use App\Managers\ServiceModuleManager;
use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\Support\Template;
use App\System\Application;
use App\System\Auth;
use App\System\License;
use App\Translation\TranslationManager;
use App\View\Blocks\BlockAdminContent;
use App\View\Renders\BlockRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController
{
    public function get(
        $pageId = "home",
        Request $request,
        Application $app,
        Auth $auth,
        License $license,
        Template $template,
        TranslationManager $translationManager,
        BlockRenderer $blockRenderer,
        UrlGenerator $url,
        PageManager $pageManager,
        WebsiteHeader $websiteHeader,
        ServiceModuleManager $serviceModuleManager
    ) {
        $page = $pageManager->getAdmin($pageId);

        if (!$page) {
            throw new EntityNotFoundException();
        }

        $user = $auth->user();
        $lang = $translationManager->user();

        $page->addScripts($request);

        $content = $blockRenderer->render(BlockAdminContent::BLOCK_ID, $request, [$page]);

        if (has_privileges("view_player_flags")) {
            $pid = "players_flags";
            $name = $lang->t($pid);
            $playersFlagsLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_user_services")) {
            $pid = "";
            foreach ($serviceModuleManager->all() as $serviceModule) {
                if ($serviceModule instanceof IServiceUserServiceAdminDisplay) {
                    $pid = "user_service?subpage=" . urlencode($serviceModule->getModuleId());
                    break;
                }
            }
            $name = $lang->t("users_services");
            $userServiceLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_income")) {
            $pid = "income";
            $name = $lang->t($pid);
            $incomeLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("manage_settings")) {
            $pid = "settings";
            $name = $lang->t($pid);
            $settingsLink = $template->render("admin/page_link", compact("pid", "name"));

            $pid = "payment_platforms";
            $name = $lang->t($pid);
            $transactionServicesLink = $template->render("admin/page_link", compact("pid", "name"));

            $pid = "pricing";
            $name = $lang->t($pid);
            $pricingLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_users")) {
            $pid = "users";
            $name = $lang->t($pid);
            $usersLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_groups")) {
            $pid = "groups";
            $name = $lang->t($pid);
            $groupsLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_servers")) {
            $pid = "servers";
            $name = $lang->t($pid);
            $serversLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_services")) {
            $pid = "services";
            $name = $lang->t($pid);
            $servicesLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_sms_codes")) {
            $pid = "sms_codes";
            $name = $lang->t($pid);
            $smsCodesLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_service_codes")) {
            $pid = "service_codes";
            $name = $lang->t($pid);
            $serviceCodesLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_antispam_questions")) {
            $pid = "antispam_questions";
            $name = $lang->t($pid);
            $antispamQuestionsLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        if (has_privileges("view_logs")) {
            $pid = "logs";
            $name = $lang->t($pid);
            $logsLink = $template->render("admin/page_link", compact("pid", "name"));
        }

        $header = $template->render("admin/header", [
            "currentPageId" => $page->getId(),
            "pageTitle" => $page->getTitle($request),
            "scripts" => $websiteHeader->getScripts(),
            "styles" => $websiteHeader->getStyles(),
        ]);
        $currentVersion = $app->version();
        $logoutAction = $url->to("/admin/login");

        return new Response(
            $template->render(
                "admin/index",
                compact(
                    "antispamQuestionsLink",
                    "content",
                    "currentVersion",
                    "groupsLink",
                    "header",
                    "incomeLink",
                    "license",
                    "logoutAction",
                    "logsLink",
                    "playersFlagsLink",
                    "pricingLink",
                    "serversLink",
                    "serviceCodesLink",
                    "servicesLink",
                    "settingsLink",
                    "smsCodesLink",
                    "transactionServicesLink",
                    "user",
                    "userServiceLink",
                    "usersLink"
                )
            )
        );
    }
}
