<?php
namespace App\Http\Controllers\View;

use App\Exceptions\EntityNotFoundException;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\Support\Template;
use App\System\Application;
use App\System\Auth;
use App\System\Heart;
use App\System\License;
use App\Translation\TranslationManager;
use App\View\Renders\BlockRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController
{
    public function action(
        $pageId = 'home',
        Request $request,
        Application $app,
        Heart $heart,
        Auth $auth,
        License $license,
        Template $template,
        TranslationManager $translationManager,
        BlockRenderer $blockRenderer
    ) {
        if (!$heart->pageExists($pageId, "admin")) {
            throw new EntityNotFoundException();
        }

        $user = $auth->user();
        $lang = $translationManager->user();

        $content = $blockRenderer->render("admincontent", $request, [$pageId]);

        // Pobranie przycisków do sidebaru
        if (get_privileges("view_player_flags")) {
            $pid = "players_flags";
            $name = $lang->t($pid);
            $playersFlagsLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_user_services")) {
            $pid = '';
            foreach ($heart->getEmptyServiceModules() as $serviceModule) {
                if ($serviceModule instanceof IServiceUserServiceAdminDisplay) {
                    $pid = "user_service?subpage=" . urlencode($serviceModule->getModuleId());
                    break;
                }
            }
            $name = $lang->t('users_services');
            $userServiceLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_income")) {
            $pid = "income";
            $name = $lang->t($pid);
            $incomeLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("manage_settings")) {
            // Ustawienia sklepu
            $pid = "settings";
            $name = $lang->t($pid);
            $settingsLink = $template->render("admin/page_link", compact('pid', 'name'));

            // Płatności
            $pid = "payment_platforms";
            $name = $lang->t($pid);
            $transactionServicesLink = $template->render("admin/page_link", compact('pid', 'name'));

            // Cennik
            $pid = "pricing";
            $name = $lang->t($pid);
            $pricingLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_users")) {
            $pid = "users";
            $name = $lang->t($pid);
            $usersLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_groups")) {
            $pid = "groups";
            $name = $lang->t($pid);
            $groupsLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_servers")) {
            $pid = "servers";
            $name = $lang->t($pid);
            $serversLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_services")) {
            $pid = "services";
            $name = $lang->t($pid);
            $servicesLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_sms_codes")) {
            // Kody SMS
            $pid = "sms_codes";
            $name = $lang->t($pid);
            $smsCodesLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_service_codes")) {
            $pid = "service_codes";
            $name = $lang->t($pid);
            $serviceCodesLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_antispam_questions")) {
            // Pytania bezpieczeństwa
            $pid = "antispam_questions";
            $name = $lang->t($pid);
            $antispamQuestionsLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_logs")) {
            // Pytania bezpieczeństwa
            $pid = "logs";
            $name = $lang->t($pid);
            $logsLink = $template->render("admin/page_link", compact('pid', 'name'));
        }

        $header = $template->render("admin/header", [
            'currentPageId' => $pageId,
            'pageTitle'     => $heart->pageTitle,
            'scripts'       => $heart->getScripts(),
            'styles'        => $heart->getStyles(),
        ]);
        $currentVersion = $app->version();

        return new Response(
            $template->render(
                "admin/index",
                compact(
                    'header',
                    'license',
                    'user',
                    'settingsLink',
                    'antispamQuestionsLink',
                    'transactionServicesLink',
                    'servicesLink',
                    'serversLink',
                    'pricingLink',
                    'userServiceLink',
                    'playersFlagsLink',
                    'usersLink',
                    'groupsLink',
                    'incomeLink',
                    'serviceCodesLink',
                    'smsCodesLink',
                    'logsLink',
                    'content',
                    'currentVersion'
                )
            )
        );
    }
}
