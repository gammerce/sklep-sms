<?php
namespace App\View\Pages\Shop;

use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PagePaymentSuccess extends Page
{
    const PAGE_ID = "payment_success";

    public function getTitle(Request $request)
    {
        return $this->lang->t("payment_success");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("shop/pages/payment_success");
    }
}
