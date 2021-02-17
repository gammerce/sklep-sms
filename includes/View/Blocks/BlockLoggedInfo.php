<?php
namespace App\View\Blocks;

use App\Support\Template;
use App\System\Auth;
use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

class BlockLoggedInfo extends Block implements IBeLoggedMust
{
    const BLOCK_ID = "logged_info";

    private Auth $auth;
    private Template $template;

    public function __construct(Auth $auth, Template $template)
    {
        $this->auth = $auth;
        $this->template = $template;
    }

    public function getContentClass(): string
    {
        return "logged_info";
    }

    public function getContent(Request $request, array $params): string
    {
        return $this->template->render("shop/layout/logged_in_information", [
            "user" => $this->auth->user(),
        ]);
    }
}
