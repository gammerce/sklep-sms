<?php
namespace App\View;

use App\Exceptions\InvalidConfigException;
use App\ServiceModules\ServiceModule;
use App\System\Application;
use App\System\Heart;

class ServiceModuleManager
{
    /** @var Application */
    private $app;

    /** @var Heart */
    private $heart;

    private $classes = [];

    public function __construct(Application $app, Heart $heart)
    {
        $this->app = $app;
        $this->heart = $heart;
    }

    /**
     * @param string $class
     * @param string $name
     * @throws InvalidConfigException
     */
    public function register($class, $name)
    {
        $id = $class::MODULE_ID;

        if (isset($this->classes[$id])) {
            throw new InvalidConfigException("There is a service with such an id: [$id] already.");
        }

        $this->classes[$id] = [
            'name' => $name,
            'class' => $class,
        ];
    }

    /**
     * Get service module with service included
     *
     * @param string $serviceId Service identifier from ss_services
     * @return ServiceModule|null
     */
    public function get($serviceId)
    {
        $service = $this->heart->getService($serviceId);

        if (!$service) {
            return null;
        }

        if (!isset($this->classes[$service->getModule()])) {
            return null;
        }

        $className = $this->classes[$service->getModule()]['class'];

        return $className ? $this->app->makeWith($className, compact('service')) : null;
    }

    /**
     * Get service module without service included
     *
     * @param $moduleId
     * @return ServiceModule|null
     */
    public function getEmpty($moduleId)
    {
        if (!isset($this->classes[$moduleId])) {
            return null;
        }

        if (!isset($this->classes[$moduleId]['class'])) {
            return null;
        }

        $classname = $this->classes[$moduleId]['class'];

        return $this->app->make($classname);
    }

    public function getName($moduleId)
    {
        if (!isset($this->classes[$moduleId])) {
            return null;
        }

        return $this->classes[$moduleId]['name'];
    }

    /**
     * @return ServiceModule[]
     */
    public function all()
    {
        return collect($this->classes)
            ->keys()
            ->map(function ($moduleId) {
                return $this->getEmpty($moduleId);
            })
            ->all();
    }
}
