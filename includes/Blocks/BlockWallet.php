<?php
namespace App\Blocks;

use App\Auth;
use App\Interfaces\IBeLoggedMust;
use App\Routes\UrlGenerator;
use App\Template;

class BlockWallet extends Block implements IBeLoggedMust
{
    public function getContentClass()
    {
        return "wallet_status";
    }

    public function getContentId()
    {
        return "wallet";
    }

    protected function content($query, $body)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = app()->make(Template::class);

        $amount = number_format($user->getWallet() / 100, 2);

        return $template->render('wallet', compact('amount'));
    }

    public function getContentEnveloped($query, $body)
    {
        /** @var UrlGenerator $url */
        $url = app()->make(UrlGenerator::class);

        $content = $this->getContent($query, $body);

        return create_dom_element("a", $content, [
            'id' => $this->getContentId(),
            'class' => $content !== null ? $this->getContentClass() : "",
            'href' => $url->to("/page/payment_log"),
        ]);
    }
}
