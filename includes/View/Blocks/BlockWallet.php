<?php
namespace App\View\Blocks;

use App\Routing\UrlGenerator;
use App\Support\Template;
use App\System\Auth;
use App\View\Html\UnescapedSimpleText;
use App\View\Interfaces\IBeLoggedMust;

class BlockWallet extends Block implements IBeLoggedMust
{
    /** @var Auth */
    private $auth;

    /** @var Template */
    private $template;

    /** @var UrlGenerator */
    private $url;

    public function __construct(Auth $auth, Template $template, UrlGenerator $url)
    {
        $this->auth = $auth;
        $this->template = $template;
        $this->url = $url;
    }

    public function getContentClass()
    {
        return "wallet_status";
    }

    public function getContentId()
    {
        return "wallet";
    }

    protected function content(array $query, array $body, array $params)
    {
        $user = $this->auth->user();
        $amount = number_format($user->getWallet() / 100, 2);

        return $this->template->render('wallet', compact('amount'));
    }

    public function getContentEnveloped(array $query, array $body, array $params)
    {
        $content = $this->getContent($query, $body, $params);

        return create_dom_element("a", new UnescapedSimpleText($content), [
            'id'    => $this->getContentId(),
            'class' => $content !== null ? $this->getContentClass() : "",
            'href'  => $this->url->to("/page/payment_log"),
        ]);
    }
}
