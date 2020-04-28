<?php
namespace App\View\Renders;

use App\Support\Template;
use App\System\License;
use App\System\Settings;
use App\View\WebsiteHeader;
use Symfony\Component\HttpFoundation\Request;

class ShopRenderer
{
    /** @var Template */
    private $template;

    /** @var License */
    private $license;

    /** @var BlockRenderer */
    private $blockRenderer;

    /** @var Settings */
    private $settings;

    /** @var WebsiteHeader */
    private $websiteHeader;

    public function __construct(
        Template $template,
        License $license,
        BlockRenderer $blockRenderer,
        Settings $settings,
        WebsiteHeader $websiteHeader
    ) {
        $this->template = $template;
        $this->license = $license;
        $this->blockRenderer = $blockRenderer;
        $this->settings = $settings;
        $this->websiteHeader = $websiteHeader;
    }

    public function render($content, $pageId, $pageTitle, Request $request)
    {
        $header = $this->template->render("header", [
            "currentPageId" => $pageId,
            "footer" => $this->license->getFooter(),
            "pageTitle" => $pageTitle,
            "scripts" => $this->websiteHeader->getScripts(),
            "styles" => $this->websiteHeader->getStyles(),
        ]);
        $loggedInfo = $this->blockRenderer->render("logged_info", $request);
        $wallet = $this->blockRenderer->render("wallet", $request);
        $servicesButtons = $this->blockRenderer->render("services_buttons", $request);
        $userButtons = $this->blockRenderer->render("user_buttons", $request);
        $googleAnalytics = $this->getGoogleAnalytics();

        $navbar = $this->template->render("navbar", compact("userButtons"));
        $footer = $this->template->render("footer");

        return $this->template->render(
            "index",
            compact(
                "content",
                "footer",
                "googleAnalytics",
                "header",
                "loggedInfo",
                "navbar",
                "pageTitle",
                "servicesButtons",
                "userButtons",
                "wallet"
            )
        );
    }

    private function getGoogleAnalytics()
    {
        return strlen($this->settings["google_analytics"])
            ? $this->template->render("google_analytics")
            : "";
    }
}
