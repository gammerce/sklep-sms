<?php
namespace App\View\Renders;

use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\Theme\Template;
use App\System\License;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Blocks\BlockLoggedInfo;
use App\View\Blocks\BlockServersButtons;
use App\View\Blocks\BlockServicesButtons;
use App\View\Blocks\BlockUserButtons;
use App\View\Blocks\BlockWallet;
use Symfony\Component\HttpFoundation\Request;

class ShopRenderer
{
    private Template $template;
    private Translator $lang;
    private License $license;
    private BlockRenderer $blockRenderer;
    private Settings $settings;
    private WebsiteHeader $websiteHeader;
    private UrlGenerator $url;

    public function __construct(
        Template $template,
        License $license,
        BlockRenderer $blockRenderer,
        Settings $settings,
        WebsiteHeader $websiteHeader,
        TranslationManager $translationManager,
        UrlGenerator $urlGenerator
    ) {
        $this->template = $template;
        $this->lang = $translationManager->user();
        $this->license = $license;
        $this->blockRenderer = $blockRenderer;
        $this->settings = $settings;
        $this->websiteHeader = $websiteHeader;
        $this->url = $urlGenerator;
    }

    public function render($content, $pageId, $pageTitle, Request $request): string
    {
        $customStyles = $this->template->render("shop/styles/general");
        $header = $this->template->render("shop/layout/header", [
            "currentPageId" => $pageId,
            "customStyles" => $customStyles,
            "footer" => $this->license->getFooter(),
            "langJsPath" => $this->url->versioned("lang.js", [
                "language" => $this->lang->getCurrentLanguage(),
            ]),
            "pageTitle" => $pageTitle,
            "scripts" => $this->websiteHeader->getScripts(),
        ]);
        $loggedInfo = $this->blockRenderer->render(BlockLoggedInfo::BLOCK_ID, $request);
        $wallet = $this->blockRenderer->render(BlockWallet::BLOCK_ID, $request);
        $serversButtons = $this->blockRenderer->render(BlockServersButtons::BLOCK_ID, $request);
        $servicesButtons = $this->blockRenderer->render(BlockServicesButtons::BLOCK_ID, $request);
        $userButtons = $this->blockRenderer->render(BlockUserButtons::BLOCK_ID, $request);
        $googleAnalytics = $this->getGoogleAnalytics();
        $contactEmail = $this->settings->getContactEmail();

        $navbar = $this->template->render(
            "shop/layout/navbar",
            compact("serversButtons", "servicesButtons", "userButtons", "wallet")
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
