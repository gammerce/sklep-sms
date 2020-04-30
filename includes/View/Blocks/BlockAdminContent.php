<?php
namespace App\View\Blocks;

use App\Managers\PageManager;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Admin\PageAdmin;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class BlockAdminContent extends Block implements IBeLoggedMust
{
    const BLOCK_ID = "admincontent";

    /** @var Translator */
    private $lang;

    /** @var PageManager */
    private $pageManager;

    public function __construct(PageManager $pageManager, TranslationManager $translationManager)
    {
        $this->pageManager = $pageManager;
        $this->lang = $translationManager->user();
    }

    public function getContentId()
    {
        return "content";
    }

    public function getContentClass()
    {
        return "";
    }

    protected function content(Request $request, array $params)
    {
        $page = $params[0];

        if (!($page instanceof PageAdmin)) {
            $page = $this->pageManager->getAdmin($page);
        }

        if (!$page) {
            throw new UnexpectedValueException("No page provided");
        }

        if (!has_privileges($page->getPrivilege())) {
            return $this->lang->t("no_privileges");
        }

        return $page->getContent($request);
    }
}
