<?php
namespace App\View\Pages\Shop;

use App\View\Interfaces\IBeLoggedCannot;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageLogin extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "login";

    public function getTitle(?Request $request = null): string
    {
        return $this->lang->t("sign_in");
    }

    public function getContent(Request $request)
    {
        $steamError = $this->pullSessionMessage($request, "steam_error");
        $emailStatus = $this->queryMessage($request, "email_status");

        return $this->template->render("shop/pages/login", compact("steamError", "emailStatus"));
    }

    private function pullSessionMessage(Request $request, string $key): string
    {
        $session = $request->getSession();

        if (!$session->has($key)) {
            return "";
        }

        $message = $this->lang->t((string) $session->get($key));
        $session->remove($key);

        return $message;
    }

    private function queryMessage(Request $request, string $key): string
    {
        $value = (string) $request->query->get($key, "");

        return strlen($value) ? $this->lang->t($value) : "";
    }
}
