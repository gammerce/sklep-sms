<?php
namespace App\Routing;

use App\System\Application;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class UrlGenerator
{
    private Settings $settings;
    private Application $app;
    private ?string $version = null;

    public function __construct(Settings $settings, Application $app)
    {
        $this->settings = $settings;
        $this->app = $app;
    }

    /**
     * @param string $path
     * @param array $query
     * @return string
     */
    public function to($path, array $query = [])
    {
        $url = rtrim($this->getShopUrl(), "/") . "/" . trim($path, "/");

        if (!empty($query)) {
            $url .= "?" . http_build_query($query);
        }

        return $url;
    }

    /**
     * @param string $path
     * @param array $query
     * @return string
     */
    public function versioned($path, $query = [])
    {
        return $this->to(
            $path,
            array_merge(
                [
                    "v" => $this->getVersion(),
                ],
                $query
            )
        );
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
