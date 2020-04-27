<?php
namespace App\View\Pages;

use Symfony\Component\HttpFoundation\Request;

class PagePaymentSuccess extends Page
{
    const PAGE_ID = "payment_success";

    public function getTitle(Request $request)
    {
        return "Płatność Zaakceptowana";
    }

    public function getContent(array $query, array $body)
    {
        return $this->template->render("payment/payment_success");
    }
}
