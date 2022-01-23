<?php
namespace App\License;

final class SubdomainUtil
{
    const PURCHASE_KEY = "subdomain";

    public static function getElement(?string $subdomain): string
    {
        if (!strlen($subdomain)) {
            return "";
        }

        $subdomainValue = htmlspecialchars($subdomain);
        $url = "https://$subdomainValue.sklep-sms.cloud";
        $hosting = __("hosting");
        return "<b>$hosting</b>: <a href=\"$url\" target=\"_blank\">$url</a><br />";
    }
}
