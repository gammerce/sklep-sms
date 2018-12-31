<?php
namespace App\Kernels;

use App\Auth;
use App\CurrentPage;
use App\Heart;
use App\License;
use App\Middlewares\IsUpToDate;
use App\Middlewares\LicenseIsValid;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAdminAuthentication;
use App\Middlewares\RunCron;
use App\Middlewares\SetAdminSession;
use App\Middlewares\SetLanguage;
use App\Middlewares\UpdateUserActivity;
use App\Settings;
use App\Template;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminKernel extends Kernel
{
    protected $middlewares = [
        SetAdminSession::class,
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ManageAdminAuthentication::class,
        LicenseIsValid::class,
        UpdateUserActivity::class,
        RunCron::class,
    ];

    public function run(Request $request)
    {
        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        /** @var CurrentPage $currentPage */
        $currentPage = $this->app->make(CurrentPage::class);

        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var License $license */
        $license = $this->app->make(License::class);

        if ($currentPage->getPid() !== 'login' && !$heart->page_exists($currentPage->getPid(),
                'admin')) {
            $currentPage->setPid('home');
        }

        // Uzytkownik nie jest zalogowany
        if ($currentPage->getPid() == "login") {
            $heart->page_title = "Login";
            $heart->style_add(
                $settings['shop_url_slash'] . "styles/admin/style_login.css?version=" . $this->app->version()
            );

            if (isset($_SESSION['info'])) {
                if ($_SESSION['info'] == "wrong_data") {
                    $text = $lang->translate('wrong_login_data');
                    $warning = $template->render("admin/login_warning", compact('text'));
                } else {
                    if ($_SESSION['info'] == "no_privilages") {
                        $text = $lang->translate('no_access');
                        $warning = $template->render("admin/login_warning", compact('text'));
                    }
                }
                unset($_SESSION['info']);
            }

            // Pobranie headera
            $header = $template->render("admin/header", compact('heart'));

            $get_data = "";
            // Fromatujemy dane get
            foreach ($_GET as $key => $value) {
                $get_data .= (!strlen($get_data) ? '?' : '&') . "{$key}={$value}";
            }

            // Pobranie szablonu logowania
            return new Response($template->render("admin/login", compact('header', 'warning')));
        }

        $content = get_content("admincontent", $request);

        // Pobranie przycisków do sidebaru
        if (get_privilages("view_player_flags")) {
            $pid = "players_flags";
            $name = $lang->translate($pid);;
            $players_flags_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_user_services")) {
            $pid = '';
            foreach ($heart->get_services_modules() as $module_data) {
                if (in_array('IService_UserServiceAdminDisplay',
                    class_implements($module_data['class']))) {
                    $pid = "user_service&subpage=" . urlencode($module_data['id']);
                    break;
                }
            };
            $name = $lang->translate('users_services');
            $user_service_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_income")) {
            $pid = "income";
            $name = $lang->translate($pid);;
            $income_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("manage_settings")) {
            // Ustawienia sklepu
            $pid = "settings";
            $name = $lang->translate($pid);;
            $settings_link = $template->render("admin/page_link", compact('pid', 'name'));

            // Płatności
            $pid = "transaction_services";
            $name = $lang->translate($pid);;
            $transaction_services_link = $template->render("admin/page_link",
                compact('pid', 'name'));

            // Taryfy
            $pid = "tariffs";
            $name = $lang->translate($pid);;
            $tariffs_link = $template->render("admin/page_link", compact('pid', 'name'));

            // Cennik
            $pid = "pricelist";
            $name = $lang->translate($pid);;
            $pricelist_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_users")) {
            $pid = "users";
            $name = $lang->translate($pid);;
            $users_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_groups")) {
            $pid = "groups";
            $name = $lang->translate($pid);;
            $groups_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_servers")) {
            $pid = "servers";
            $name = $lang->translate($pid);;
            $servers_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_services")) {
            $pid = "services";
            $name = $lang->translate($pid);;
            $services_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_sms_codes")) {
            // Kody SMS
            $pid = "sms_codes";
            $name = $lang->translate($pid);;
            $sms_codes_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_service_codes")) {
            $pid = "service_codes";
            $name = $lang->translate($pid);;
            $service_codes_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_antispam_questions")) {
            // Pytania bezpieczeństwa
            $pid = "antispam_questions";
            $name = $lang->translate($pid);;
            $antispam_questions_link = $template->render("admin/page_link", compact('pid', 'name'));
        }
        if (get_privilages("view_logs")) {
            // Pytania bezpieczeństwa
            $pid = "logs";
            $name = $lang->translate($pid);;
            $logs_link = $template->render("admin/page_link", compact('pid', 'name'));
        }

        // Pobranie headera
        $header = $template->render("admin/header", compact('heart'));

        $currentVersion = $this->app->version();

        // Pobranie ostatecznego szablonu
        return new Response($template->render(
            "admin/index",
            compact('header', 'license', 'user', 'settings_link',
                'antispam_questions_link', 'transaction_services_link', 'services_link',
                'servers_link', 'tariffs_link', 'pricelist_link', 'user_service_link',
                'players_flags_link', 'users_link', 'groups_link', 'income_link',
                'service_codes_link', 'sms_codes_link', 'logs_link', 'content', 'currentVersion')
        ));
    }
}
