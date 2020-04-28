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
    const PAGE_ID = 'payment_error';

    public function getTitle(Request $request)
    {
        return "Płatność Odrzucona";
    }

    public function getContent(Request $request)
    {
        return $this->template->render("payment/payment_error");
    }
}
