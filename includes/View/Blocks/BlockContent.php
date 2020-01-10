<?php
namespace App\View\Blocks;

use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\CurrentPage;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;

class BlockContent extends Block
{
    /** @var Heart */
    private $heart;

    /** @var CurrentPage */
    private $currentPage;

    /** @var Translator */
    private $lang;

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
        $page = $this->heart->getPage($this->currentPage->getPid());

        if ($page instanceof IBeLoggedMust && !is_logged()) {
            return $this->lang->t('must_be_logged_in');
        }

        if ($page instanceof IBeLoggedCannot && is_logged()) {
            return $this->lang->t('must_be_logged_out');
        }

        return $this->content($query, $body);
    }

    protected function content(array $query, array $body)
    {
        $page = $this->heart->getPage($this->currentPage->getPid());
        return $page ? $page->getContent($query, $body) : null;
    }
}
