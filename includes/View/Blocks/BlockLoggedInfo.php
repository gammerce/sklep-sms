<?php
namespace App\View\Blocks;

use App\Support\Template;
use App\System\Auth;
use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

class BlockLoggedInfo extends Block implements IBeLoggedMust
{
    /** @var Auth */
    private $auth;

    /** @var Template */
    private $template;

    public function __construct(Auth $auth, Template $template)
    {
        $this->auth = $auth;
        $this->template = $template;
    }

    public function getContentClass()
    {
        return "logged_info";
    }

    public function getContentId()
    {
        return "logged_info";
    }

    protected function content(Request $request, array $params)
    {
        return $this->template->render("logged_in_informations", [
            "user" => $this->auth->user(),
        ]);
    }
}
