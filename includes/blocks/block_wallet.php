<?php

$heart->register_block("wallet", "BlockWallet");

class BlockWallet extends Block implements I_BeLoggedMust
{
    public function get_content_class()
    {
        return "wallet_status";
    }

    public function get_content_id()
    {
        return "wallet";
    }

    protected function content($get, $post)
    {
        global $user, $settings, $lang, $templates;

        $amount = number_format($user->getWallet() / 100, 2);

        $output = eval($templates->render('wallet'));

        return $output;
    }

    public function get_content_enveloped($get, $post)
    {
        $content = $this->get_content($get, $post);

        return create_dom_element("a", $content, [
            'id'    => $this->get_content_id(),
            'class' => $content !== null ? $this->get_content_class() : "",
            'href'  => "index.php?pid=payment_log",
        ]);
    }
}