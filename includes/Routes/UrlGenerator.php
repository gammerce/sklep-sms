<?php
namespace App\Routes;

use App\Application;
use App\Settings;
use Symfony\Component\HttpFoundation\Request;

class UrlGenerator
{
    /** @var Settings */
    private $settings;

    /** @var Application */
    private $app;

    public function __construct(Settings $settings, Application $app)
    {
        $this->settings = $settings;
        $this->app = $app;
    }

    public function to($path)
    {
        return rtrim($this->getShopUrl(), '/') . '/' . trim($path, "/");
    }

    public function versioned($path)
    {
        $url = $this->to($path);

        if (str_contains($url, '?')) {
            return $url . "&version={$this->app->version()}";
        }

        return $url . "?version={$this->app->version()}";
    }

    public function getShopUrl()
    {
        if ($this->settings['shop_url']) {
            return $this->settings['shop_url'];
        }

        /** @var Request $request */
        $request = $this->app->make(Request::class);

        return $request->getSchemeAndHttpHost();
    }
}
