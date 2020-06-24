<?php
namespace App\View\Pages\Shop;

use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageRegister extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "register";

    /** @var string */
    private $siteKey;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        $siteKey
    ) {
        parent::__construct($template, $translationManager);
        $this->siteKey = $siteKey;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("sign_up");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("shop/pages/register", [
            "siteKey" => $this->siteKey,
        ]);
    }
}
