<?php
namespace App\License;

final class SubdomainUtil
{
    const PURCHASE_KEY = "subdomain";

    public static function getText(?string $subdomain): string
    {
        if (!strlen($subdomain)) {
            return "";
        }

        $url = self::getUrl($subdomain);
        $hosting = __("hosting");
        return "<b>$hosting</b>: $url<br />";
    }

    public static function getLink(?string $subdomain): string
    {
        if (!strlen($subdomain)) {
            return "";
        }

        $url = self::getUrl($subdomain);
        $hosting = __("hosting");
        return "<b>$hosting</b>: <a href=\"$url\" target=\"_blank\">$url</a><br />";
    }

    private static function getUrl(string $subdomain): string
    {
        $subdomainValue = htmlspecialchars($subdomain);
        return "https://$subdomainValue.sklep-sms.cloud";
    }
}
