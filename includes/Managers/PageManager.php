<?php
namespace App\Managers;

use App\Exceptions\InvalidConfigException;
use App\System\Application;
use App\View\Pages\Admin\PageAdmin;
use App\View\Pages\Page;

class PageManager
{
    private Application $app;

    /** @var string[] */
    private array $classes = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function registerUser($className): void
    {
        $pageId = $className::PAGE_ID;
        $this->register($pageId, $className, "user");
    }

    public function registerAdmin($className): void
    {
        $pageId = $className::PAGE_ID;
        $this->register($pageId, $className, "admin");
    }

    /**
     * @param string $pageId
     * @param string $class
     * @param string $type
     * @throws InvalidConfigException
     */
    private function register($pageId, $class, $type): void
    {
        if ($this->exists($pageId, $type)) {
            throw new InvalidConfigException("There is a page with such an id: [$pageId] already.");
        }

        $this->classes[$type][$pageId] = $class;
    }

    /**
     * @param string $pageId
     * @param string $type
     * @return bool
     */
    private function exists($pageId, $type): bool
    {
        return isset($this->classes[$type][$pageId]);
    }

    /**
     * @param string $pageId
     * @return Page|null
     */
    public function getUser($pageId): ?Page
    {
        return $this->get($pageId, "user");
    }

    /**
     * @param string $pageId
     * @return PageAdmin|null
     */
    public function getAdmin($pageId): ?PageAdmin
    {
        return $this->get($pageId, "admin");
    }

    /**
     * @param string $pageId
     * @param string $type
     * @return Page|null
     */
    private function get($pageId, $type): ?Page
    {
        if ($this->exists($pageId, $type)) {
            $classname = $this->classes[$type][$pageId];
            return $this->app->make($classname);
        }

        return null;
    }
}
