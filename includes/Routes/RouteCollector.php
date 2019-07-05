<?php
namespace App\Routes;

use FastRoute\RouteCollector as BaseRouteCollector;

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
}
