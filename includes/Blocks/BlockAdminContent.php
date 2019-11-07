<?php
namespace App\Blocks;

use App\System\CurrentPage;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class BlockAdminContent extends Block
{
    /** @var Heart */
    protected $heart;

    /** @var CurrentPage */
    protected $page;

    /** @var Translator */
    protected $lang;

    public function __construct(
        Heart $heart,
        CurrentPage $page,
        TranslationManager $translationManager
    ) {
        $this->heart = $heart;
        $this->page = $page;
        $this->lang = $translationManager->user();
    }

    public function getContentClass()
    {
        return "custom_content";
    }

    public function getContentId()
    {
        return "content";
    }

    // Nadpisujemy getContent, aby wyswieltac info gdy nie jest zalogowany lub jest zalogowany, lecz nie powinien
    public function getContent(array $query, array $body)
    {
        if (!is_logged()) {
            return $this->lang->translate('must_be_logged_in');
        }

        return $this->content($query, $body);
    }

    protected function content(array $query, array $body)
    {
        if (($page = $this->heart->getPage($this->page->getPid(), "admin")) === null) {
            return null;
        }

        return $page->getContent($query, $body);
    }
}
