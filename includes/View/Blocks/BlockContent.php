<?php
namespace App\View\Blocks;

use App\Exceptions\AccessProhibitedException;
use App\Exceptions\UnauthorizedException;
use App\Managers\PageManager;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

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
            return null;
        }

        if ($page instanceof IBeLoggedMust && !is_logged()) {
            throw new UnauthorizedException();
        }

        if ($page instanceof IBeLoggedCannot && is_logged()) {
            throw new AccessProhibitedException();
        }

        return $page->getContent($request);
    }
}
