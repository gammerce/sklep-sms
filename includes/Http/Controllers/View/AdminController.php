<?php
namespace App\Http\Controllers\View;

use App\Exceptions\EntityNotFoundException;
use App\Routing\UrlGenerator;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\System\Application;
use App\System\Auth;
use App\System\Heart;
use App\System\License;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Renders\BlockRenderer;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        CurrentPage $currentPage,
        Template $template,
        TranslationManager $translationManager,
        BlockRenderer $blockRenderer,
        UrlGenerator $url
    ) {
        if ($currentPage->getPid() !== "login") {
            if (!$heart->pageExists($pageId, "admin")) {
                throw new EntityNotFoundException();
            }

            $currentPage->setPid($pageId);
        }

        $session = $request->getSession();
        $user = $auth->user();
        $lang = $translationManager->user();

        if ($currentPage->getPid() === "login") {
            $heart->pageTitle = "Login";

            $warning = "";
            if ($session->has("info")) {
                if ($session->get("info") == "wrong_data") {
                    $text = $lang->t('wrong_login_data');
                    $warning = $template->render("admin/login_warning", compact('text'));
                } elseif ($session->get("info") == "no_privileges") {
                    $text = $lang->t('no_access');
                    $warning = $template->render("admin/login_warning", compact('text'));
                }
                $session->remove("info");
            }

            $header = $this->renderHeader($heart, $currentPage, $template);
            $action = $url->to("/admin", $request->query->all());

            return new Response(
                $template->render("admin/login", compact('header', 'warning', 'action'))
            );
        }

        $content = $blockRenderer->render("admincontent", $request);

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

        $header = $this->renderHeader($heart, $currentPage, $template);
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

    /**
     * @deprecated
     */
    public function oldGet(Request $request, UrlGenerator $url)
    {
        $path = "/admin";

        $query = $request->query->all();

        if (array_key_exists("pid", $query)) {
            $path .= "/{$query["pid"]}";
            unset($query["pid"]);
        }

        return new RedirectResponse($url->to($path, $query), 301);
    }

    private function renderHeader(Heart $heart, CurrentPage $currentPage, Template $template)
    {
        return $template->render("admin/header", [
            'currentPageId' => $currentPage->getPid(),
            'pageTitle' => $heart->pageTitle,
            'scripts' => $heart->getScripts(),
            'styles' => $heart->getStyles(),
        ]);
    }
}
