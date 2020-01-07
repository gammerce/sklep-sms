<?php
namespace App\Blocks;

use App\Interfaces\IBeLoggedCannot;
use App\Interfaces\IBeLoggedMust;
use App\Pages\Page;
use App\System\CurrentPage;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class BlockContent extends Block
{
    /** @var Heart */
    protected $heart;

    /** @var CurrentPage */
    protected $currentPage;

    /** @var Translator */
    protected $lang;

    /** @var Page */
    protected $page;

    public function __construct(
        Heart $heart,
        CurrentPage $currentPage,
        TranslationManager $translationManager
    ) {
        $this->heart = $heart;
        $this->currentPage = $currentPage;
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

    // Nadpisujemy get_content, aby wyswieltac info gdy nie jest zalogowany lub jest zalogowany, lecz nie powinien
    public function getContent(array $query, array $body)
    {
        if (($this->page = $this->heart->getPage($this->currentPage->getPid())) === null) {
            return null;
        }

        if ($this->page instanceof IBeLoggedMust && !is_logged()) {
            return $this->lang->t('must_be_logged_in');
        }

        if ($this->page instanceof IBeLoggedCannot && is_logged()) {
            return $this->lang->t('must_be_logged_out');
        }

        return $this->content($query, $body);
    }

    protected function content(array $query, array $body)
    {
        return $this->page->getContent($query, $body);
    }
}
