<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedCannot;
use Symfony\Component\HttpFoundation\Request;

class PageForgottenPassword extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "forgotten_password";

    public function getTitle(Request $request)
    {
        return $this->lang->t("forgotten_password");
    }

    public function getContent(array $query, array $body)
    {
        return $this->template->render("forgotten_password");
    }
}
