<?php
namespace App\View\Pages\Shop;

use App\Support\Template;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageContact extends Page
{
    const PAGE_ID = "contact";

    private Settings $settings;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Settings $settings
    ) {
        parent::__construct($template, $translationManager);
        $this->settings = $settings;
    }

    public function getTitle(Request $request): string
    {
        return $this->lang->t("contact");
    }

    public function getContent(Request $request)
    {
        $email = $this->settings->getContactEmail();
        $gg = array_get($this->settings, "gadugadu");

        $emailSection = $email
            ? $this->template->render("shop/components/contact/email", compact("email"))
            : null;
        $ggSection = $gg
            ? $this->template->render("shop/components/contact/gadugadu", compact("gg"))
            : null;

        return $this->template->render("shop/pages/contact", compact("emailSection", "ggSection"));
    }
}
