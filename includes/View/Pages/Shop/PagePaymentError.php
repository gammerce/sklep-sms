<?php
/**
 * Created by MilyGosc.
 * URL: https://forum.sklep-sms.pl/showthread.php?tid=88
 */
namespace App\View\Pages\Shop;

use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PagePaymentError extends Page
{
    const PAGE_ID = "payment_error";

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("payment_rejected");
    }

    public function getContent(Request $request)
    {
        return $this->template->render("shop/pages/payment_error");
    }
}
