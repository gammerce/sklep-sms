<?php
namespace App\Controllers;

use App\Application;
use App\Auth;
use App\CurrentPage;
use App\Heart;
use App\License;
use App\Settings;
use App\Template;
use App\TranslationManager;
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
        Settings $settings,
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
            $settings,
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
        Settings $settings,
        Template $template,
        TranslationManager $translationManager
    ) {
        $session = $request->getSession();
        $user = $auth->user();
        $lang = $translationManager->user();

        if (
            $currentPage->getPid() !== "login" &&
            !$heart->page_exists($currentPage->getPid(), 'admin')
        ) {
            $currentPage->setPid('home');
        }

        // Uzytkownik nie jest zalogowany
        if ($currentPage->getPid() == "login") {
            $heart->page_title = "Login";
            $heart->style_add(
                $settings['shop_url_slash'] .
                    "styles/admin/style_login.css?version=" .
                    $app->version()
            );

            if ($session->has("info")) {
                if ($session->get("info") == "wrong_data") {
                    $text = $lang->translate('wrong_login_data');
                    $warning = $template->render("admin/login_warning", compact('text'));
                } else {
                    if ($session->get("info") == "no_privilages") {
                        $text = $lang->translate('no_access');
                        $warning = $template->render("admin/login_warning", compact('text'));
                    }
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
        if (get_privilages("view_player_flags")) {
            $pid = "players_flags";
            $name = $lang->translate($pid);
            $players_flags_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_user_services")) {
            $pid = '';
            foreach ($heart->get_services_modules() as $module_data) {
                if (
                    in_array(
                        'IService_UserServiceAdminDisplay',
                        class_implements($module_data['class'])
                    )
                ) {
                    $pid = "user_service?subpage=" . urlencode($module_data['id']);
                    break;
                }
            }
            $name = $lang->translate('users_services');
            $user_service_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_income")) {
            $pid = "income";
            $name = $lang->translate($pid);
            $income_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("manage_settings")) {
            // Ustawienia sklepu
            $pid = "settings";
            $name = $lang->translate($pid);
            $settings_link = $template->render("admin/page_link", compact('pid', 'name'));

            // Płatności
            $pid = "transaction_services";
            $name = $lang->translate($pid);
            $transaction_services_link = $template->render(
                "admin/page_link",
                compact('pid', 'name')
            );

            // Taryfy
            $pid = "tariffs";
            $name = $lang->translate($pid);
            $tariffs_link = $template->render("admin/page_link", compact('pid', 'name'));

            // Cennik
            $pid = "pricelist";
            $name = $lang->translate($pid);
            $pricelist_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_users")) {
            $pid = "users";
            $name = $lang->translate($pid);
            $users_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_groups")) {
            $pid = "groups";
            $name = $lang->translate($pid);
            $groups_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_servers")) {
            $pid = "servers";
            $name = $lang->translate($pid);
            $servers_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_services")) {
            $pid = "services";
            $name = $lang->translate($pid);
            $services_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_sms_codes")) {
            // Kody SMS
            $pid = "sms_codes";
            $name = $lang->translate($pid);
            $sms_codes_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_service_codes")) {
            $pid = "service_codes";
            $name = $lang->translate($pid);
            $service_codes_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_antispam_questions")) {
            // Pytania bezpieczeństwa
            $pid = "antispam_questions";
            $name = $lang->translate($pid);
            $antispam_questions_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_logs")) {
            // Pytania bezpieczeństwa
            $pid = "logs";
            $name = $lang->translate($pid);
            $logs_link = $template->render("admin/page_link", compact('pid', 'name'));
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
                    'settings_link',
                    'antispam_questions_link',
                    'transaction_services_link',
                    'services_link',
                    'servers_link',
                    'tariffs_link',
                    'pricelist_link',
                    'user_service_link',
                    'players_flags_link',
                    'users_link',
                    'groups_link',
                    'income_link',
                    'service_codes_link',
                    'sms_codes_link',
                    'logs_link',
                    'content',
                    'currentVersion'
                )
            )
        );
    }
}
