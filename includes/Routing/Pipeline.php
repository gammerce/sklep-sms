<?php
namespace App\Routing;

use App\System\Application;
use Closure;
use Symfony\Component\HttpFoundation\Request;

class Pipeline
{
    private Application $app;
    private Request $passable;

    /** @var string[] */
    private array $pipes;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function send(Request $passable)
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * @param string[] $pipes
     * @return Pipeline
     */
    public function through(array $pipes)
    {
        $this->pipes = $pipes;
        return $this;
    }

    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    private function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_array($pipe)) {
                    $middlewareClass = $pipe[0];
                    $args = $pipe[1];
                } else {
                    $middlewareClass = $pipe;
                    $args = [];
                }

                $middleware = $this->app->make($middlewareClass);

                return $middleware->handle($passable, $args, $stack);
            };
        };
    }

    private function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }
}
