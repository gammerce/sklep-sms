<?php

$heart->register_block("admincontent", "BlockAdminContent");

class BlockAdminContent extends Block
{
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
        global $lang;

        if (!is_logged()) {
            return $lang->translate('must_be_logged_in');
        }

        return $this->content($get, $post);
    }

    protected function content($get, $post)
    {
        global $heart, $G_PID;

        if (($page = $heart->get_page($G_PID, "admin")) === null) {
            return null;
        }

        return $page->get_content($get, $post);
    }
}