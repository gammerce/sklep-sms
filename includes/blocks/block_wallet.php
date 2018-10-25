<?php

use App\Auth;
use App\Template;

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
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = app()->make(Template::class);

        $amount = number_format($user->getWallet() / 100, 2);

        return $template->render2('wallet', compact('amount'));
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