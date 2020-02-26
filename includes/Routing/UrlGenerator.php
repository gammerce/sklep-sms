<?php
namespace App\Routing;

use App\System\Application;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class UrlGenerator
{
    /** @var Settings */
    private $settings;

    /** @var Application */
    private $app;

    /** @var string|null */
    private $version;

    public function __construct(Settings $settings, Application $app)
    {
        $this->settings = $settings;
        $this->app = $app;
    }

    public function to($path, array $query = [])
    {
        $url = rtrim($this->getShopUrl(), '/') . '/' . trim($path, '/');

        if (!empty($query)) {
            $queryString = http_build_query($query);
            $url .= "?$queryString";
        }

        return $url;
    }

    public function versioned($path)
    {
        $url = $this->to($path);

        if (str_contains($url, '?')) {
            return $url . "&v={$this->getVersion()}";
        }

        return $url . "?v={$this->getVersion()}";
    }

    public function getShopUrl()
    {
        if ($this->settings->getShopUrl()) {
            return $this->settings->getShopUrl();
        }

        /** @var Request $request */
        $request = $this->app->make(Request::class);

        return $request->getUriForPath("");
    }

    private function getVersion()
    {
        if (!$this->version) {
            $version = hash("sha256", $this->settings->getSecret() . "#" . $this->app->version());
            $this->version = substr($version, 0, 7);
        }

        return $this->version;
    }
}
