<?php
namespace App\View\Pages\Shop;

use App\System\Settings;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PagePrivacyPolicy extends Page
{
    const PAGE_ID = "privacy";

    private Settings $settings;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Settings $settings
    ) {
        parent::__construct($template, $translationManager);
        $this->settings = $settings;
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("privacy_policy");
    }

    public function getContent(Request $request)
    {
        $shopEmail = $this->settings->getContactEmail();
        $shopUrl = $this->settings->getShopUrl();

        return $this->template->render(
            "shop/pages/privacy_policy",
            compact("shopEmail", "shopUrl")
        );
    }
}
