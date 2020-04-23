<?php
namespace App\View\Blocks;

use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class BlockAdminContent extends Block
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

    public function getContentId()
    {
        return "content";
    }

    protected function content(array $query, array $body, array $params)
    {
        if (!is_logged()) {
            return $this->lang->t('must_be_logged_in');
        }

        $pageId = $params[0];
        $page = $this->heart->getPage($pageId, "admin");

        if ($page) {
            return $page->getContent($query, $body);
        }

        return null;
    }
}
