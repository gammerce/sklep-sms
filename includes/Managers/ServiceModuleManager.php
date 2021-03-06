<?php
namespace App\Managers;

use App\Exceptions\InvalidConfigException;
use App\ServiceModules\ServiceModule;
use App\System\Application;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServiceModuleManager
{
    private Application $app;
    private ServiceManager $serviceManager;
    private Translator $lang;

    private array $classes = [];

    public function __construct(
        Application $app,
        ServiceManager $serviceManager,
        TranslationManager $translationManager
    ) {
        $this->app = $app;
        $this->serviceManager = $serviceManager;
        $this->lang = $translationManager->user();
    }

    /**
     * @param string $class
     * @param string $name
     * @throws InvalidConfigException
     */
    public function register($class, $name): void
    {
        $id = $class::MODULE_ID;

        if (isset($this->classes[$id])) {
            throw new InvalidConfigException("There is a service with such an id: [$id] already.");
        }

        $this->classes[$id] = compact("name", "class");
    }

    /**
     * Get service module with service included
     *
     * @param string $serviceId Service identifier from ss_services
     * @return ServiceModule|null
     */
    public function get($serviceId): ?ServiceModule
    {
        $service = $this->serviceManager->get($serviceId);

        if (!$service) {
            return null;
        }

        if (!isset($this->classes[$service->getModule()])) {
            return null;
        }

        $className = $this->classes[$service->getModule()]["class"];

        return $className ? $this->app->makeWith($className, compact("service")) : null;
    }

    /**
     * Get service module without service included
     *
     * @param $moduleId
     * @return ServiceModule|null
     */
    public function getEmpty($moduleId): ?ServiceModule
    {
        if (!isset($this->classes[$moduleId])) {
            return null;
        }

        if (!isset($this->classes[$moduleId]["class"])) {
            return null;
        }

        $classname = $this->classes[$moduleId]["class"];

        return $this->app->make($classname);
    }

    public function getName($moduleId): ?string
    {
        if (!isset($this->classes[$moduleId])) {
            return null;
        }

        return $this->lang->t($this->classes[$moduleId]["name"]);
    }

    /**
     * @return ServiceModule[]
     */
    public function all(): array
    {
        return collect($this->classes)
            ->keys()
            ->map(fn($moduleId) => $this->getEmpty($moduleId))
            ->all();
    }
}
