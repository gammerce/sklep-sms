<?php
namespace App\Http\Controllers\View;

use App\Services\Interfaces\IServiceUserServiceAdminDisplay;
use App\System\Application;
use App\System\Auth;
use App\System\CurrentPage;
use App\System\Heart;
use App\System\License;
use App\System\Template;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController
{
    public function action(
        $pageId = null,
        Request $request,
        Application $app,
        Heart $heart,
        Auth $auth,
        License $license,
        CurrentPage $currentPage,
        Template $template,
        TranslationManager $translationManager
    ) {
        if ($currentPage->getPid() !== "login") {
            $currentPage->setPid($pageId);
        }

        return $this->oldAction(
            $request,
            $app,
            $heart,
            $auth,
            $license,
            $currentPage,
            $template,
            $translationManager
        );
    }

    public function oldAction(
        Request $request,
        Application $app,
        Heart $heart,
        Auth $auth,
        License $license,
        CurrentPage $currentPage,
        Template $template,
        TranslationManager $translationManager
    ) {
        $session = $request->getSession();
        $user = $auth->user();
        $lang = $translationManager->user();

        if (
            $currentPage->getPid() !== "login" &&
            !$heart->pageExists($currentPage->getPid(), 'admin')
        ) {
            $currentPage->setPid('home');
        }

        // Uzytkownik nie jest zalogowany
        if ($currentPage->getPid() == "login") {
            $heart->pageTitle = "Login";

            $warning = "";
            if ($session->has("info")) {
                if ($session->get("info") == "wrong_data") {
                    $text = $lang->translate('wrong_login_data');
                    $warning = $template->render("admin/login_warning", compact('text'));
                } elseif ($session->get("info") == "no_privileges") {
                    $text = $lang->translate('no_access');
                    $warning = $template->render("admin/login_warning", compact('text'));
                }
                $session->remove("info");
            }

            // Pobranie headera
            $header = $template->render("admin/header", compact('currentPage', 'heart'));

            $action = rtrim(
                $request->getPathInfo() . "?" . http_build_query($request->query->all()),
                "?"
            );

            // Pobranie szablonu logowania
            return new Response(
                $template->render("admin/login", compact('header', 'warning', 'action'))
            );
        }

        $content = get_content("admincontent", $request);

        // Pobranie przycisków do sidebaru
        if (get_privileges("view_player_flags")) {
            $pid = "players_flags";
            $name = $lang->translate($pid);
            $playersFlagsLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_user_services")) {
            $pid = '';
            foreach ($heart->getServicesModules() as $moduleData) {
                if (
                    in_array(
                        IServiceUserServiceAdminDisplay::class,
                        class_implements($moduleData['class'])
                    )
                ) {
                    $pid = "user_service?subpage=" . urlencode($moduleData['id']);
                    break;
                }
            }
            $name = $lang->translate('users_services');
            $userServiceLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_income")) {
            $pid = "income";
            $name = $lang->translate($pid);
            $incomeLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("manage_settings")) {
            // Ustawienia sklepu
            $pid = "settings";
            $name = $lang->translate($pid);
            $settingsLink = $template->render("admin/page_link", compact('pid', 'name'));

            // Płatności
            $pid = "transaction_services";
            $name = $lang->translate($pid);
            $transactionServicesLink = $template->render("admin/page_link", compact('pid', 'name'));

            // Taryfy
            $pid = "tariffs";
            $name = $lang->translate($pid);
            $tariffsLink = $template->render("admin/page_link", compact('pid', 'name'));

            // Cennik
            $pid = "pricelist";
            $name = $lang->translate($pid);
            $pricelistLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_users")) {
            $pid = "users";
            $name = $lang->translate($pid);
            $usersLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_groups")) {
            $pid = "groups";
            $name = $lang->translate($pid);
            $groupsLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_servers")) {
            $pid = "servers";
            $name = $lang->translate($pid);
            $serversLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_services")) {
            $pid = "services";
            $name = $lang->translate($pid);
            $servicesLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_sms_codes")) {
            // Kody SMS
            $pid = "sms_codes";
            $name = $lang->translate($pid);
            $smsCodesLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_service_codes")) {
            $pid = "service_codes";
            $name = $lang->translate($pid);
            $serviceCodesLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_antispam_questions")) {
            // Pytania bezpieczeństwa
            $pid = "antispam_questions";
            $name = $lang->translate($pid);
            $antispamQuestionsLink = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privileges("view_logs")) {
            // Pytania bezpieczeństwa
            $pid = "logs";
            $name = $lang->translate($pid);
            $logsLink = $template->render("admin/page_link", compact('pid', 'name'));
        }

        // Pobranie headera
        $header = $template->render("admin/header", compact('currentPage', 'heart'));

        $currentVersion = $app->version();

        // Pobranie ostatecznego szablonu
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
                    'tariffsLink',
                    'pricelistLink',
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
