<?php
namespace App\Routing;

use FastRoute\RouteCollector as BaseRouteCollector;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RouteCollector extends BaseRouteCollector
{
    /** @var array */
    protected $currentGroupMiddlewares = [];

    /**
     * @param string|array $prefix
     * @param callable $callback
     */
    public function addGroup($prefix, callable $callback)
    {
        $middlewares = [];

        if (is_array($prefix)) {
            $middlewares = array_get($prefix, "middlewares", []);
            $prefix = array_get($prefix, "prefix", "");
        }

        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousGroupMiddlewares = $this->currentGroupMiddlewares;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $this->currentGroupMiddlewares = array_merge($previousGroupMiddlewares, $middlewares);

        $callback($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupMiddlewares = $previousGroupMiddlewares;
    }

    public function addRoute($httpMethod, $route, $handler)
    {
        $handler["middlewares"] = array_merge(
            $this->currentGroupMiddlewares,
            array_get($handler, "middlewares", [])
        );
        parent::addRoute($httpMethod, $route, $handler);
    }

    public function redirect($from, $to, $status = 302)
    {
        $this->get($from, [
            'uses' => function () use ($to, $status) {
                /** @var UrlGenerator $url */
                $url = app()->make(UrlGenerator::class);
                return new RedirectResponse($url->to($to), $status);
            },
        ]);
    }

    public function redirectPermanent($from, $to)
    {
        $this->redirect($from, $to, 301);
    }
}
