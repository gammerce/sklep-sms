<?php
namespace App\Kernels;

use App\CurrentPage;
use App\Heart;
use App\License;
use App\Middlewares\IsUpToDate;
use App\Middlewares\LicenseIsValid;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\RunCron;
use App\Middlewares\SetLanguage;
use App\Middlewares\UpdateUserActivity;
use App\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexKernel extends Kernel
{
    protected $middlewares = [
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
        $header = $template->render("header", compact('heart', 'license'));

        // Pobranie ostatecznego szablonu
        $output = $template->render(
            "index",
            compact("header", "heart", "logged_info", "wallet", "services_buttons", "content", "user_buttons")
        );

        return new Response($output);
    }
}
