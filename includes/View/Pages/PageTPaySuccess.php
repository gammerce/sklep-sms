<?php
/**
 * Created by MilyGosc.
 * URL: https://forum.sklep-sms.pl/showthread.php?tid=88
 */
namespace App\View\Pages;

use Symfony\Component\HttpFoundation\Request;

class PageTPaySuccess extends Page
{
    const PAGE_ID = "tpay_success";

    public function getTitle(Request $request)
    {
        return "Płatność Zaakceptowana";
    }

    public function getContent(array $query, array $body)
    {
        return $this->template->render("payment/tpay_success");
    }
}
