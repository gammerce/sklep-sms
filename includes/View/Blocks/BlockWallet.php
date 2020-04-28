<?php
namespace App\View\Blocks;

use App\Routing\UrlGenerator;
use App\Support\Template;
use App\System\Auth;
use App\View\Html\RawText;
use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

class BlockWallet extends Block implements IBeLoggedMust
{
    const BLOCK_ID = "wallet";

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

    protected function content(Request $request, array $params)
    {
        $user = $this->auth->user();
        $amount = number_format($user->getWallet() / 100, 2);

        return $this->template->render("shop/layout/wallet", compact("amount"));
    }

    public function getContentEnveloped(Request $request, array $params)
    {
        $content = $this->getContent($request, $params);

        return create_dom_element("a", new RawText($content), [
            "id" => $this->getContentId(),
            "class" => $content !== null ? $this->getContentClass() : "",
            "href" => $this->url->to("/page/payment_log"),
        ]);
    }
}
