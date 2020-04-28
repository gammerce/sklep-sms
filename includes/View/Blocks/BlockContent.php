<?php
namespace App\View\Blocks;

use App\Managers\PageManager;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class BlockContent extends Block
{
    const BLOCK_ID = "content";

    /** @var Translator */
    private $lang;

    /** @var PageManager */
    private $pageManager;

    public function __construct(PageManager $pageManager, TranslationManager $translationManager)
    {
        $this->lang = $translationManager->user();
        $this->pageManager = $pageManager;
    }

    public function getContentClass()
    {
        return "site-content";
    }

    public function getContentId()
    {
        return "content";
    }

    protected function content(Request $request, array $params)
    {
        $page = $params[0];

        if (!($page instanceof Page)) {
            $page = $this->pageManager->getUser($page);
        }

        if (!$page) {
            throw new UnexpectedValueException("No page provided");
        }

        if ($page instanceof IBeLoggedMust && !is_logged()) {
            return $this->lang->t('must_be_logged_in');
        }

        if ($page instanceof IBeLoggedCannot && is_logged()) {
            return $this->lang->t('must_be_logged_out');
        }

        return $page->getContent($request);
    }
}
