<?php

use App\Auth;
use App\Heart;
use App\Template;
use App\Translator;

$heart->register_block("user_buttons", "BlockUserButtons");

class BlockUserButtons extends Block
{
    public function get_content_class()
    {
        return is_logged() ? "user_buttons" : "loginarea";
    }

    public function get_content_id()
    {
        return "user_buttons";
    }

    protected function content($get, $post)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = app()->make(Template::class);

        /** @var Translator $lang */
        $lang = app()->make(Translator::class);

        /** @var Heart $heart */
        $heart = app()->make(Heart::class);

        if (!$auth->check()) {
            return eval($template->render("loginarea"));
        }

        // Panel Admina
        if (get_privilages("acp", $user)) {
            $acp_button = create_dom_element("li", create_dom_element("a", $lang->translate('acp'), [
                'href' => "admin.php",
            ]));
        }

        // Doładowanie portfela
        if ($heart->user_can_use_service($user->getUid(), $heart->get_service("charge_wallet"))) {
            $charge_wallet_button = create_dom_element("li",
                create_dom_element("a", $lang->translate('charge_wallet'), [
                    'href' => "index.php?pid=purchase&service=charge_wallet",
                ]));
        }

        return eval($template->render("user_buttons"));
    }
}