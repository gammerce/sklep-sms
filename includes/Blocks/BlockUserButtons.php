<?php
namespace App\Blocks;

use App\Auth;
use App\Heart;
use App\Routes\UrlGenerator;
use App\Template;
use App\TranslationManager;

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

        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var Heart $heart */
        $heart = app()->make(Heart::class);

        /** @var UrlGenerator $url */
        $url = app()->make(UrlGenerator::class);

        if (!$auth->check()) {
            return $template->render("loginarea");
        }

        // Panel Admina
        if (get_privilages("acp", $user)) {
            $acp_button = create_dom_element(
                "li",
                create_dom_element("a", $lang->translate('acp'), [
                    'href' => $url->to("/admin"),
                ])
            );
        }

        // Doładowanie portfela
        if ($heart->user_can_use_service($user->getUid(), $heart->get_service("charge_wallet"))) {
            $charge_wallet_button = create_dom_element(
                "li",
                create_dom_element("a", $lang->translate('charge_wallet'), [
                    'href' => $url->to("/page/purchase?service=charge_wallet"),
                ])
            );
        }

        return $template->render("user_buttons", compact('acp_button', 'charge_wallet_button'));
    }
}
