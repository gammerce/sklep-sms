<?php

use App\CurrentPage;
use App\Heart;
use App\Interfaces\IBeLoggedCannot;
use App\Interfaces\IBeLoggedMust;
use App\Translator;

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

    public function __construct(Heart $heart, CurrentPage $currentPage, Translator $lang)
    {
        $this->heart = $heart;
        $this->currentPage = $currentPage;
        $this->lang = $lang;
    }

    public function get_content_class()
    {
        return "content";
    }

    public function get_content_id()
    {
        return "content";
    }

    // Nadpisujemy get_content, aby wyswieltac info gdy nie jest zalogowany lub jest zalogowany, lecz nie powinien
    public function get_content($get, $post)
    {
        if (($this->page = $this->heart->get_page($this->currentPage->getPid())) === null) {
            return null;
        }

        if ($this->page instanceof IBeLoggedMust && !is_logged()) {
            return $this->lang->translate('must_be_logged_in');
        }

        if ($this->page instanceof IBeLoggedCannot && is_logged()) {
            return $this->lang->translate('must_be_logged_out');
        }

        return $this->content($get, $post);
    }

    protected function content($get, $post)
    {
        return $this->page->get_content($get, $post);
    }
}
