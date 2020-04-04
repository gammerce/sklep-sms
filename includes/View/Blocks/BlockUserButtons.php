<?php
namespace App\View\Blocks;

use App\Routing\UrlGenerator;
use App\Support\Template;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class BlockUserButtons extends Block
{
    /** @var Auth */
    private $auth;

    /** @var Template */
    private $template;

    /** @var Heart */
    private $heart;

    /** @var UrlGenerator */
    private $url;

    /** @var Translator */
    private $lang;

    public function __construct(
        Auth $auth,
        Template $template,
        TranslationManager $translationManager,
        Heart $heart,
        UrlGenerator $url
    ) {
        $this->auth = $auth;
        $this->template = $template;
        $this->heart = $heart;
        $this->url = $url;
        $this->lang = $translationManager->user();
    }

    public function getContentClass()
    {
        return is_logged() ? "user_buttons" : "loginarea";
    }

    public function getContentId()
    {
        return "user_buttons";
    }

    protected function content(array $query, array $body, array $params)
    {
        if (!$this->auth->check()) {
            return $this->template->render("loginarea");
        }

        $user = $this->auth->user();
        $acpButton = "";

        // Panel Admina
        if (get_privileges("acp", $user)) {
            $acpButton = create_dom_element(
                "li",
                create_dom_element("a", $this->lang->t('acp'), [
                    'href' => $this->url->to("/admin"),
                ])
            );
        }

        // Doładowanie portfela
        if ($this->heart->canUserUseService($user->getUid(), $this->heart->getService("charge_wallet"))) {
            $chargeWalletButton = create_dom_element(
                "li",
                create_dom_element("a", $this->lang->t('charge_wallet'), [
                    'href' => $this->url->to("/page/purchase?service=charge_wallet"),
                ])
            );
        }

        return $this->template->render("user_buttons", compact('acpButton', 'chargeWalletButton'));
    }
}
