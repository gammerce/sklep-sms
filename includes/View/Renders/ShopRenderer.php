<?php
namespace App\View\Renders;

use App\Support\Template;
use App\System\Heart;
use App\System\License;
use App\System\Settings;
use App\View\CurrentPage;
use Symfony\Component\HttpFoundation\Request;

class ShopRenderer
{
    /** @var Template */
    private $template;

    /** @var Heart */
    private $heart;

    /** @var License */
    private $license;

    /** @var CurrentPage */
    private $currentPage;

    /** @var BlockRenderer */
    private $blockRenderer;

    /** @var Settings */
    private $settings;

    public function __construct(
        Template $template,
        Heart $heart,
        License $license,
        CurrentPage $currentPage,
        BlockRenderer $blockRenderer,
        Settings $settings
    ) {
        $this->template = $template;
        $this->heart = $heart;
        $this->license = $license;
        $this->currentPage = $currentPage;
        $this->blockRenderer = $blockRenderer;
        $this->settings = $settings;
    }

    public function render($content, $pageTitle, Request $request)
    {
        $header = $this->template->render("header", [
            'currentPageId' => $this->currentPage->getPid(),
            'license' => $this->license,
            'pageTitle' => $pageTitle,
            'scripts' => $this->heart->getScripts(),
            'styles' => $this->heart->getStyles(),
        ]);
        $loggedInfo = $this->blockRenderer->render("logged_info", $request);
        $wallet = $this->blockRenderer->render("wallet", $request);
        $servicesButtons = $this->blockRenderer->render("services_buttons", $request);
        $userButtons = $this->blockRenderer->render("user_buttons", $request);
        $googleAnalytics = $this->getGoogleAnalytics();

        return $this->template->render(
            "index",
            compact(
                "content",
                "googleAnalytics",
                "header",
                "loggedInfo",
                "pageTitle",
                "servicesButtons",
                "userButtons",
                "wallet"
            )
        );
    }

    private function getGoogleAnalytics()
    {
        return strlen($this->settings['google_analytics'])
            ? $this->template->render('google_analytics')
            : '';
    }
}
