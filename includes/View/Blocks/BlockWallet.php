<?php
namespace App\View\Blocks;

use App\View\Html\UnescapedSimpleText;
use App\View\Interfaces\IBeLoggedMust;
use App\Routes\UrlGenerator;
use App\System\Auth;
use App\System\Template;

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

    protected function content(array $query, array $body)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = app()->make(Template::class);

        $amount = number_format($user->getWallet() / 100, 2);

        return $template->render('wallet', compact('amount'));
    }

    public function getContentEnveloped(array $query, array $body)
    {
        /** @var UrlGenerator $url */
        $url = app()->make(UrlGenerator::class);

        $content = $this->getContent($query, $body);

        return create_dom_element("a", new UnescapedSimpleText($content), [
            'id' => $this->getContentId(),
            'class' => $content !== null ? $this->getContentClass() : "",
            'href' => $url->to("/page/payment_log"),
        ]);
    }
}
