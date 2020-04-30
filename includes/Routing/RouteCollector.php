<?php
namespace App\Routing;

use FastRoute\RouteCollector as BaseRouteCollector;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RouteCollector extends BaseRouteCollector
{
    /** @var array */
    private $currentGroupMiddlewares = [];

    /** @var string|null */
    private $currentGroupType;

    /**
     * @param string|array $prefix
     * @param callable $callback
     */
    public function addGroup($prefix, callable $callback)
    {
        $middlewares = [];
        $type = null;

        if (is_array($prefix)) {
            $type = array_get($prefix, "type");
            $middlewares = array_get($prefix, "middlewares", []);
            $prefix = array_get($prefix, "prefix", "");
        }

        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousGroupType = $this->currentGroupType;
        $previousGroupMiddlewares = $this->currentGroupMiddlewares;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $this->currentGroupType = $type;
        $this->currentGroupMiddlewares = array_merge($previousGroupMiddlewares, $middlewares);

        $callback($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupType = $previousGroupType;
        $this->currentGroupMiddlewares = $previousGroupMiddlewares;
    }

    public function addRoute($httpMethod, $route, $handler)
    {
        $handler["middlewares"] = array_merge(
            $this->currentGroupMiddlewares,
            array_get($handler, "middlewares", [])
        );
        $handler["type"] = array_get($handler, "type", $this->currentGroupType);
        parent::addRoute($httpMethod, $route, $handler);
    }

    public function redirect($from, $to, $status = Response::HTTP_FOUND)
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
        $this->redirect($from, $to, Response::HTTP_MOVED_PERMANENTLY);
    }
}
