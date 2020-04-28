<?php
namespace App\View;

use App\Exceptions\InvalidConfigException;
use App\System\Application;
use App\View\Pages\Admin\PageAdmin;
use App\View\Pages\Page;

class PageManager
{
    /** @var Application */
    private $app;

    private $classes = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function registerUser($className)
    {
        $pageId = $className::PAGE_ID;
        $this->register($pageId, $className, "user");
    }

    public function registerAdmin($className)
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
    private function register($pageId, $class, $type)
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
    private function exists($pageId, $type)
    {
        return isset($this->classes[$type][$pageId]);
    }

    /**
     * @param string $pageId
     * @return Page|null
     */
    public function getUser($pageId)
    {
        return $this->get($pageId, "user");
    }

    /**
     * @param string $pageId
     * @return PageAdmin|null
     */
    public function getAdmin($pageId)
    {
        return $this->get($pageId, "admin");
    }

    /**
     * @param string $pageId
     * @param string $type
     * @return Page|null
     */
    private function get($pageId, $type)
    {
        if ($this->exists($pageId, $type)) {
            $classname = $this->classes[$type][$pageId];
            return $this->app->make($classname);
        }

        return null;
    }
}
