<?php

use App\CurrentPage;
use App\Heart;
use App\Translator;

class BlockAdminContent extends Block
{
    /** @var Heart */
    protected $heart;

    /** @var CurrentPage */
    protected $page;

    /** @var Translator */
    protected $lang;

    public function __construct(Heart $heart, CurrentPage $page, Translator $lang)
    {
        $this->heart = $heart;
        $this->page = $page;
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
        if (!is_logged()) {
            return $this->lang->translate('must_be_logged_in');
        }

        return $this->content($get, $post);
    }

    protected function content($get, $post)
    {
        if (($page = $this->heart->get_page($this->page->getPid(), "admin")) === null) {
            return null;
        }

        return $page->get_content($get, $post);
    }
}