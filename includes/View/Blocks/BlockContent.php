<?php
namespace App\View\Blocks;

use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;

class BlockContent extends Block
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    public function __construct(Heart $heart, TranslationManager $translationManager)
    {
        $this->heart = $heart;
        $this->lang = $translationManager->user();
    }

    public function getContentClass()
    {
        return "custom-content";
    }

    public function getContentId()
    {
        return "content";
    }

    protected function content(array $query, array $body, array $params)
    {
        $pageId = $params[0];
        $page = $this->heart->getPage($pageId);

        if (!$page) {
            return null;
        }

        if ($page instanceof IBeLoggedMust && !is_logged()) {
            return $this->lang->t('must_be_logged_in');
        }

        if ($page instanceof IBeLoggedCannot && is_logged()) {
            return $this->lang->t('must_be_logged_out');
        }

        return $page->getContent($query, $body);
    }
}
