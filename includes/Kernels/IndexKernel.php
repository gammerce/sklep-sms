<?php
namespace App\Kernels;

use App\CurrentPage;
use App\Heart;
use App\License;
use App\Middlewares\DecodeGetAttributes;
use App\Middlewares\IsUpToDate;
use App\Middlewares\LicenseIsValid;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\RunCron;
use App\Middlewares\SetLanguage;
use App\Middlewares\UpdateUserActivity;
use App\Settings;
use App\Template;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexKernel extends Kernel
{
    protected $middlewares = [
        DecodeGetAttributes::class,
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ManageAuthentication::class,
        LicenseIsValid::class,
        UpdateUserActivity::class,
        RunCron::class,
    ];

    public function run(Request $request)
    {
        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var License $license */
        $license = $this->app->make(License::class);

        /** @var CurrentPage $currentPage */
        $currentPage = $this->app->make(CurrentPage::class);

        /** @var Template $template */
        $template = $this->app->make(Template::class);

        if (!$heart->page_exists($currentPage->getPid())) {
            $currentPage->setPid('home');
        }

        // Pobranie miejsca logowania
        $logged_info = get_content("logged_info", $request);

        // Pobranie portfela
        $wallet = get_content("wallet", $request);

        // Pobranie zawartości
        $content = get_content("content", $request);

        // Pobranie przycisków usług
        $services_buttons = get_content("services_buttons", $request);

        // Pobranie przycisków użytkownika
        $user_buttons = get_content("user_buttons", $request);

        // Pobranie headera
        $header = eval($template->render("header"));

        // Pobranie ostatecznego szablonu
        $output = eval($template->render("index"));

        return new Response($output);
    }
}
