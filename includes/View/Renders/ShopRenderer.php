<?php
namespace App\View\Renders;

use App\Managers\WebsiteHeader;
use App\Theme\Template;
use App\System\License;
use App\System\Settings;
use App\View\Blocks\BlockLoggedInfo;
use App\View\Blocks\BlockServicesButtons;
use App\View\Blocks\BlockUserButtons;
use App\View\Blocks\BlockWallet;
use Symfony\Component\HttpFoundation\Request;

class ShopRenderer
{
    private Template $template;
    private License $license;
    private BlockRenderer $blockRenderer;
    private Settings $settings;
    private WebsiteHeader $websiteHeader;

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

    public function render($content, $pageId, $pageTitle, Request $request): string
    {
        $header = $this->template->render("shop/layout/header", [
            "currentPageId" => $pageId,
            "footer" => $this->license->getFooter(),
            "pageTitle" => $pageTitle,
            "scripts" => $this->websiteHeader->getScripts(),
            "styles" => $this->websiteHeader->getStyles(),
        ]);
        $loggedInfo = $this->blockRenderer->render(BlockLoggedInfo::BLOCK_ID, $request);
        $wallet = $this->blockRenderer->render(BlockWallet::BLOCK_ID, $request);
        $servicesButtons = $this->blockRenderer->render(BlockServicesButtons::BLOCK_ID, $request);
        $userButtons = $this->blockRenderer->render(BlockUserButtons::BLOCK_ID, $request);
        $googleAnalytics = $this->getGoogleAnalytics();
        $contactEmail = $this->settings->getContactEmail();

        $navbar = $this->template->render(
            "shop/layout/navbar",
            compact("servicesButtons", "userButtons", "wallet")
        );
        $contactColumn = $contactEmail
            ? $this->template->render("shop/components/footer/contact", [
                "email" => $contactEmail,
            ])
            : null;
        $footer = $this->template->render("shop/layout/footer", compact("contactColumn"));

        return $this->template->render(
            "shop/index",
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

    private function getGoogleAnalytics(): string
    {
        return strlen($this->settings["google_analytics"])
            ? $this->template->render("shop/layout/google_analytics")
            : "";
    }
}
