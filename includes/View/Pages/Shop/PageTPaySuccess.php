<?php
/**
 * Created by MilyGosc.
 * URL: https://forum.sklep-sms.pl/showthread.php?tid=88
 */
namespace App\View\Pages\Shop;

use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageTPaySuccess extends Page
{
    const PAGE_ID = "tpay_success";

    public function getTitle(Request $request)
    {
        return "Płatność Zaakceptowana";
    }

    public function getContent(Request $request)
    {
        return $this->template->render("payment/tpay_success");
    }
}